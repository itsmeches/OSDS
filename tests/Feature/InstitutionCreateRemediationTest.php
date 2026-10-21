<?php

namespace Tests\Feature;

use App\Filament\Resources\Institutions\Pages\CreateInstitution;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\InstitutionCreationService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class InstitutionCreateRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'K3R', 'name' => 'Disposable K3R region']);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
        $grant = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        $this->actingAs($actor);
        $data = ['name' => 'Disposable K3R institution', 'official_id' => 'K3R-CONTROL', 'primary_region_id' => $region->id, 'status' => 'active'];
        $page = Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data);

        return [$actor, $grant, $data, $page];
    }

    public static function auditFaults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('auditFaults')]
    public function test_real_audit_insert_and_database_rejection_roll_back_entire_operation(string $mode): void
    {
        [$actor, , $data, $page] = $this->fixture();
        $count = AuditEvent::count();
        $depth = DB::transactionLevel();
        $pdo = DB::connection()->getPdo();
        Log::spy();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($mode, $depth, $pdo) {
            $this->assertSame(Institution::class.'.created', $args[0]);
            $this->assertSame($depth + 1, DB::transactionLevel());
            $this->assertSame($pdo, DB::connection()->getPdo());
            $this->assertSame($pdo, (new AuditEvent)->getConnection()->getPdo());
            if ($mode === 'database') {
                $invalidActor = new User;
                $invalidActor->id = -1;
                $args[2] = $invalidActor;
            }
            $row = (new AuditRecorder)->record(...$args);
            $this->assertTrue($row->exists);
            throw new RuntimeException('ISOLATED_K3_AFTER_AUDIT');
        });
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $this->assertSame(0, Institution::count());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame($depth, DB::transactionLevel());
        $this->assertSame($data['name'], $page->get('data.name'));
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)]);
        foreach (['ISOLATED_K3_AFTER_AUDIT', 'SQLSTATE', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed' && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function changes(): array
    {
        return array_map(fn ($v) => [$v], ['permission', 'panel', 'inactive', 'deactivated', 'revoked', 'expired', 'future']);
    }

    #[DataProvider('changes')]
    public function test_fresh_authority_is_required_after_mount(string $change): void
    {
        [$actor, $grant, , $page] = $this->fixture();
        if (in_array($change, ['permission', 'panel'])) {
            $actor->revokePermissionTo($change === 'permission' ? 'institutions.manage' : 'platform.admin.access');
        } elseif (in_array($change, ['inactive', 'deactivated'])) {
            DB::table('users')->where('id', $actor->id)->update($change === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        } else {
            DB::table('user_region_assignments')->where('id', $grant->id)->update(match ($change) {
                'revoked' => ['revoked_at' => now()],
                'expired' => ['ends_at' => now()->subMinute()],
                'future' => ['starts_at' => now()->addHour()],
            });
        }
        $before = AuditEvent::count();
        $page->call('create');
        if (in_array($change, ['revoked', 'expired', 'future'])) {
            $page->assertHasFormErrors(['primary_region_id']);
        } else {
            $page->assertForbidden();
        }
        $this->assertSame(0, Institution::count());
        $this->assertSame($before, AuditEvent::count());
    }

    public function test_optional_null_region_and_separate_creations_preserve_existing_semantics(): void
    {
        [$actor, , $data] = $this->fixture();
        $data['primary_region_id'] = null;
        $data['official_id'] = null;
        $before = AuditEvent::count();
        foreach ([1, 2] as $attempt) {
            Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data)->call('create')->assertHasNoFormErrors()->assertRedirect();
        }
        $this->assertSame(2, Institution::count());
        $this->assertSame($before + 2, AuditEvent::count());
        foreach (Institution::all() as $row) {
            $this->assertNull($row->primary_region_id);
            $this->assertSame('hei', $row->organization_type);
            $this->assertFalse($row->is_synthetic);
        }
    }

    public function test_detached_actor_is_denied_by_authoritative_service(): void
    {
        [, , $data] = $this->fixture();
        $other = User::factory()->create(['is_synthetic' => true]);
        $other->givePermissionTo(['platform.admin.access', 'institutions.manage']);
        $before = AuditEvent::count();
        try {
            app(InstitutionCreationService::class)->create($other, array_replace($data, ['primary_region_id' => null]));
            $this->fail('Detached actor must be denied.');
        } catch (AuthorizationException) {
            $this->assertSame(0, Institution::count());
            $this->assertSame($before, AuditEvent::count());
        }
    }

    public function test_existing_nationwide_alternative_and_null_region_without_regional_authority(): void
    {
        [$actor, $grant, $data] = $this->fixture();
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['revoked_at' => now()]);
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm(array_replace($data, ['primary_region_id' => null]))->call('create')->assertHasNoFormErrors()->assertRedirect();
        $this->assertNull(Institution::sole()->primary_region_id);
        NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Disposable K3 nationwide fixture']);
        Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm(array_replace($data, ['official_id' => 'K3R-NATIONAL']))->call('create')->assertHasNoFormErrors()->assertRedirect();
        $this->assertSame($data['primary_region_id'], Institution::where('official_id', 'K3R-NATIONAL')->sole()->primary_region_id);
        $this->assertSame($before + 2, AuditEvent::count());
    }
}
