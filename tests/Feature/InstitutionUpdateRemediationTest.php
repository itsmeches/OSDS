<?php

namespace Tests\Feature;

use App\Filament\Resources\Institutions\Pages\EditInstitution;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\InstitutionUpdateService;
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

class InstitutionUpdateRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $regions = collect(['A', 'B', 'C'])->map(fn ($key) => Region::create(['code' => 'K1R-'.$key, 'name' => 'Disposable K1R '.$key]));
        $institution = Institution::create(['name' => 'K1R original', 'primary_region_id' => $regions[0]->id, 'is_synthetic' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
        $grants = UserRegionAssignment::withoutEvents(fn () => collect([$regions[0], $regions[1]])->map(fn ($r) => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $r->id, 'starts_at' => now()->subDay()])));
        $this->actingAs($actor);
        $page = Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id]);
        $data = ['name' => 'K1R changed', 'official_id' => 'K1R-OFFICIAL', 'primary_region_id' => $regions[1]->id, 'status' => 'inactive'];

        return [$actor, $institution, $regions, $grants, $page, $data];
    }

    public static function auditFaults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('auditFaults')]
    public function test_real_audit_insert_or_database_rejection_rolls_back_on_same_connection(string $mode): void
    {
        [, $institution, , , $page, $data] = $this->fixture();
        $before = $institution->fresh()->getRawOriginal();
        $count = AuditEvent::count();
        $depth = DB::transactionLevel();
        $pdo = DB::connection()->getPdo();
        Log::spy();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($mode, $depth, $pdo) {
            $this->assertSame($depth + 1, DB::transactionLevel());
            $this->assertSame($pdo, DB::connection()->getPdo());
            $this->assertSame($pdo, (new AuditEvent)->getConnection()->getPdo());
            if ($mode === 'database') {
                $actor = new User;
                $actor->id = -1;
                $args[2] = $actor;
            }
            $row = (new AuditRecorder)->record(...$args);
            $this->assertTrue($row->exists);
            throw new RuntimeException('ISOLATED_K1_PARTIAL_AUDIT');
        });
        $this->app->instance(AuditRecorder::class, $audit);
        $page->fillForm($data)->call('save', false)->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $this->assertSame($before, $institution->fresh()->getRawOriginal());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame($depth, DB::transactionLevel());
        $this->assertSame($data['name'], $page->get('data.name'));
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)]);
        $this->assertStringNotContainsString('ISOLATED_K1_PARTIAL_AUDIT', $response);
        $this->assertStringNotContainsString('SQLSTATE', $response);
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed' && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function authorityChanges(): array
    {
        return array_map(fn ($v) => [$v], ['permission', 'panel', 'inactive', 'deactivated', 'current-revoked', 'current-expired', 'current-future', 'destination-revoked', 'destination-expired', 'institution-moved']);
    }

    #[DataProvider('authorityChanges')]
    public function test_current_authority_is_required_after_mount(string $change): void
    {
        [$actor, $institution, $regions, $grants, $page, $data] = $this->fixture();
        if ($change === 'permission' || $change === 'panel') {
            $actor->revokePermissionTo($change === 'permission' ? 'institutions.manage' : 'platform.admin.access');
        } elseif (in_array($change, ['inactive', 'deactivated'])) {
            DB::table('users')->where('id', $actor->id)->update($change === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        } elseif ($change === 'institution-moved') {
            DB::table('institutions')->where('id', $institution->id)->update(['primary_region_id' => $regions[2]->id]);
        } else {
            $grant = str_starts_with($change, 'destination') ? $grants[1] : $grants[0];
            DB::table('user_region_assignments')->where('id', $grant->id)->update(str_ends_with($change, 'revoked') ? ['revoked_at' => now()] : (str_ends_with($change, 'future') ? ['starts_at' => now()->addHour()] : ['ends_at' => now()->subMinute()]));
        }
        $before = $institution->fresh()->getRawOriginal();
        $count = AuditEvent::count();
        $page->fillForm($data)->call('save', false);
        if (str_starts_with($change, 'destination')) {
            $page->assertHasFormErrors(['primary_region_id']);
        } elseif (in_array($change, ['permission', 'current-revoked', 'current-expired', 'current-future', 'institution-moved'])) {
            $page->assertNotFound();
        } else {
            $page->assertForbidden();
        }
        $this->assertSame($before, $institution->fresh()->getRawOriginal());
        $this->assertSame($count, AuditEvent::count());
    }

    public function test_forged_identity_attributes_do_not_persist_and_audit_actor_is_current(): void
    {
        [$actor, $institution, , , $page, $data] = $this->fixture();
        $page->update(updates: array_merge(collect($data)->mapWithKeys(fn ($v, $k) => ['data.'.$k => $v])->all(), ['data.organization_type' => 'other', 'data.is_synthetic' => false, 'data.granted_by' => -1, 'data.actor_id' => -1]), calls: [['method' => 'save', 'params' => [false], 'path' => '']])->assertHasNoFormErrors();
        $this->assertTrue($institution->fresh()->is_synthetic);
        $this->assertSame('hei', $institution->fresh()->organization_type);
        $this->assertSame($actor->id, AuditEvent::where('action', Institution::class.'.updated')->sole()->actor_id);
    }

    public function test_repeat_noop_and_two_mounted_editors_preserve_existing_last_writer_semantics(): void
    {
        [$actor, $institution, , , $first, $data] = $this->fixture();
        $second = Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id]);
        $count = AuditEvent::count();
        $first->fillForm($data)->call('save', false)->assertHasNoFormErrors();
        $first->call('save', false)->assertHasNoFormErrors();
        $this->assertSame($count + 1, AuditEvent::count());
        $second->fillForm(array_replace($data, ['name' => 'K1R second explicit writer']))->call('save', false)->assertHasNoFormErrors();
        $this->assertSame('K1R second explicit writer', $institution->fresh()->name);
        $this->assertSame($count + 2, AuditEvent::count());
    }

    public function test_optional_null_region_and_whitelisted_validation_remain_supported(): void
    {
        [$actor, $institution, , , $page, $data] = $this->fixture();
        $page->fillForm(array_replace($data, ['name' => '']))->call('save', false)->assertHasFormErrors(['name']);
        $page->fillForm(array_replace($data, ['primary_region_id' => null]))->call('save', false)->assertHasNoFormErrors();
        $this->assertNull($institution->fresh()->primary_region_id);
        $this->assertSame(1, AuditEvent::where('action', Institution::class.'.updated')->count());
    }

    public function test_detached_actor_service_call_is_denied(): void
    {
        [$actor, $institution, , , , $data] = $this->fixture();
        auth()->logout();
        $this->expectException(AuthorizationException::class);
        app(InstitutionUpdateService::class)->update($actor, $institution, $data);
    }

    public static function canonicalAlternatives(): array
    {
        return [['nationwide'], ['representation']];
    }

    #[DataProvider('canonicalAlternatives')]
    public function test_existing_canonical_scope_alternatives_remain_supported(string $kind): void
    {
        [$actor, $institution, , $grants, $page, $data] = $this->fixture();
        DB::table('user_region_assignments')->whereIn('id', $grants->pluck('id'))->update(['revoked_at' => now()]);
        if ($kind === 'nationwide') {
            NationwideAccessGrant::withoutEvents(fn () => NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subHour(), 'reason' => 'Disposable canonical scope']));
        } else {
            InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create(['user_id' => $actor->id, 'institution_id' => $institution->id, 'starts_at' => now()->subHour(), 'verified_at' => now(), 'verified_by' => $actor->id]));
            // The existing region selector does not substitute representation
            // for region grants. Its optional null value remains available.
            $data['primary_region_id'] = null;
        }
        $page->fillForm($data)->call('save', false)->assertHasNoFormErrors();
        $this->assertSame($data['name'], $institution->fresh()->name);
        $this->assertSame($data['primary_region_id'], $institution->fresh()->primary_region_id);
        $this->assertSame(1, AuditEvent::where('action', Institution::class.'.updated')->count());
    }
}
