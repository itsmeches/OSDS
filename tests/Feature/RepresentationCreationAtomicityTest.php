<?php

namespace Tests\Feature;

use App\Filament\Resources\InstitutionRepresentatives\Pages\CreateInstitutionRepresentative;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Authorization\StaffListScope;
use App\Services\Identity\AccountGovernance;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RepresentationCreationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'G007', 'name' => 'Disposable representation region']);
        $institution = Institution::create(['name' => 'Disposable representation institution', 'primary_region_id' => $region->id, 'is_synthetic' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        // The existing CHEDRO permission bundle can delegate to SCHOOL. The
        // earlier Admin fixture lacked accounts.create.school and was denied.
        $actor->assignRole(AccountRole::Chedro->value);
        $target = User::factory()->create(['is_synthetic' => true]);
        $target->assignRole(AccountRole::School->value);
        // Fixture scope makes both real relationship selectors usable. Events
        // are suppressed only for setup; the tested creation uses its observer.
        UserRegionAssignment::withoutEvents(function () use ($actor, $target, $region) {
            foreach ([$actor, $target] as $user) {
                UserRegionAssignment::create(['user_id' => $user->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
            }
        });
        $this->actingAs($actor);
        $this->assertTrue($actor->isActive() && $actor->can('platform.admin.access') && $actor->can('institution_representatives.assign') && $actor->can('accounts.create.school'));
        $this->assertTrue(app(AccountGovernance::class)->canManageTargetRole($actor, $target));
        $this->assertTrue(app(ScopeAuthorizer::class)->canAccessInstitution($actor, $institution));
        $this->assertTrue(app(StaffListScope::class)->users(User::query(), $actor, 'institution_representatives.assign')->whereKey($target->id)->exists());
        $this->assertTrue(app(StaffListScope::class)->institutions(Institution::query(), $actor, 'institution_representatives.assign')->whereKey($institution->id)->exists());
        $data = ['user_id' => $target->id, 'institution_id' => $institution->id, 'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()];
        $page = Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data);
        $page->assertOk();

        return [$actor, $target, $institution, $data, $page];
    }

    public function test_authorized_real_create_persists_representation_and_one_matching_audit(): void
    {
        [$actor, $target, $institution, $data, $page] = $this->fixture();
        $before = AuditEvent::count();
        $page->call('create')->assertHasNoFormErrors()->assertRedirect();
        $row = InstitutionRepresentative::where('user_id', $target->id)->where('institution_id', $institution->id)->sole();
        $audit = AuditEvent::where('action', InstitutionRepresentative::class.'.created')->where('resource_type', InstitutionRepresentative::class)->where('resource_id', (string) $row->id)->sole();
        $this->assertSame($actor->id, $row->granted_by);
        $this->assertSame($data['starts_at'], $row->starts_at->toDateTimeString());
        $this->assertSame($data['ends_at'], $row->ends_at->toDateTimeString());
        $this->assertNull($row->verified_at);
        $this->assertNull($row->revoked_at);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame($institution->id, $audit->institution_id);
        $this->assertSame('success', $audit->outcome);
        $this->assertSame($before + 1, AuditEvent::count());
    }

    public function test_actual_representation_creation_rolls_back_when_mandatory_audit_fails(): void
    {
        [, $target, $institution, , $page] = $this->fixture();
        $this->assertFalse(InstitutionRepresentative::where('user_id', $target->id)->where('institution_id', $institution->id)->exists());
        $before = AuditEvent::count();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()
            ->withArgs(fn ($action, ...$rest) => $action === (new InstitutionRepresentative)->getMorphClass().'.created')
            ->andThrow(new RuntimeException('Isolated representation creation audit fault'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['starts_at'])->assertNotified('Action could not be confirmed');
        $this->assertSame($before, AuditEvent::count());
        $this->assertFalse(InstitutionRepresentative::where('user_id', $target->id)->where('institution_id', $institution->id)->exists(), 'A failed mandatory audit must roll back the representation grant.');
    }
}
