<?php

namespace Tests\Feature;

use App\Filament\Resources\Regions\Pages\CreateRegion;
use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RegionCreateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view', 'regions.manage']);
        $this->actingAs($actor);

        return [$actor, ['code' => 'K5-CONTROL', 'name' => 'Disposable K5 region', 'is_active' => false]];
    }

    private function evidence(string $case, array $data): void
    {
        $file = base_path('docs/evidence/phase-3d7-k5-region-create-after.json');
        $all = $case === 'authorized' || ! is_file($file) ? [] : json_decode(file_get_contents($file), true);
        $all[$case] = $data;
        file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    public function test_authorized_actual_create_persists_fields_uuid_and_one_core_audit_without_regional_grants(): void
    {
        [$actor, $data] = $this->fixture();
        $this->assertSame(0, $actor->regionAssignments()->count());
        $this->assertSame(0, $actor->nationwideAccessGrants()->count());
        $before = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data);
        $this->assertFalse($page->instance()->hasDatabaseTransactions());
        $page->call('create')->assertHasNoFormErrors()->assertRedirect();
        $row = Region::sole();
        $this->assertSame($data, $row->only(array_keys($data)));
        $this->assertTrue(Str::isUuid($row->public_id));
        $audit = AuditEvent::where('action', Region::class.'.created')->where('resource_id', (string) $row->id)->sole();
        $this->assertSame(Region::class, $audit->resource_type);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertNull($audit->region_id);
        $this->assertNull($audit->institution_id);
        $this->assertSame('success', $audit->outcome);
        $this->assertSame($before + 1, AuditEvent::count());
        $this->evidence('authorized', ['regions_before' => 0, 'region' => $row->only(['id', 'public_id', 'code', 'name', 'is_active']), 'regional_grants' => 0, 'nationwide_grants' => 0, 'audit_before' => $before, 'audit_after' => AuditEvent::count(), 'audit' => $audit->only(['action', 'resource_type', 'resource_id', 'actor_id', 'region_id', 'institution_id', 'outcome', 'metadata'])]);
    }

    public static function denials(): array
    {
        return [['initial-permission'], ['mounted-permission'], ['inactive'], ['deactivated']];
    }

    #[DataProvider('denials')]
    public function test_existing_permission_and_active_identity_boundaries_deny_without_creation(string $mode): void
    {
        [$actor, $data] = $this->fixture();
        $page = $mode === 'mounted-permission' ? Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data) : null;
        if (str_ends_with($mode, 'permission')) {
            $actor->revokePermissionTo('regions.manage');
        } else {
            $actor->update($mode === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        }
        $this->actingAs($actor->fresh());
        $before = AuditEvent::count();
        if ($page) {
            $page->call('create')->assertForbidden();
        } else {
            Livewire::test(CreateRegion::class)->assertForbidden();
        }
        $this->assertSame(0, Region::count());
        $this->assertSame($before, AuditEvent::count());
        $this->evidence('denied-'.$mode, ['status' => 403, 'regions' => 0, 'action_audit_delta' => 0]);
    }

    public function test_actual_http_route_requires_staff_panel_admission(): void
    {
        [$actor] = $this->fixture();
        $actor->revokePermissionTo('platform.admin.access');
        $this->actingAs($actor->fresh());
        $before = AuditEvent::count();
        $this->get('/admin/regions/create')->assertForbidden();
        $this->assertSame(0, Region::count());
        $this->assertSame($before, AuditEvent::count());
        $this->evidence('denied-panel-http', ['status' => 403, 'regions' => 0, 'action_audit_delta' => 0]);
    }

    public function test_invalid_required_oversized_and_duplicate_fields_leave_records_unchanged(): void
    {
        [$actor, $data] = $this->fixture();
        $existing = Region::create(['code' => 'K5-EXISTS', 'name' => 'Disposable K5 uniqueness fixture', 'is_active' => true]);
        $saved = $existing->fresh()->getRawOriginal();
        $before = AuditEvent::count();
        foreach ([['code' => ''], ['code' => str_repeat('X', 33)], ['code' => $existing->code], ['name' => ''], ['name' => str_repeat('X', 256)]] as $invalid) {
            Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm(array_replace($data, $invalid))->call('create')->assertHasFormErrors(array_keys($invalid));
            $this->assertSame(1, Region::count());
            $this->assertSame($saved, $existing->fresh()->getRawOriginal());
            $this->assertSame($before, AuditEvent::count());
        }
        $this->evidence('invalid-input', ['cases' => 5, 'existing_region' => $existing->only(['id', 'code', 'name', 'is_active']), 'new_regions' => 0, 'action_audit_delta' => 0]);
    }

    public function test_default_activity_and_protected_identity_attribution_fields_use_actual_form(): void
    {
        [$actor, $data] = $this->fixture();
        unset($data['is_active']);
        $before = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data);
        $this->assertTrue($page->get('data.is_active'));
        $page->update(updates: ['data.id' => -1, 'data.public_id' => 'FORGED', 'data.actor_id' => -1, 'data.region_id' => -1, 'data.institution_id' => -1, 'data.created_at' => '2000-01-01 00:00:00'], calls: [['method' => 'create', 'params' => [], 'path' => '']])->assertHasNoFormErrors()->assertRedirect();
        $row = Region::sole();
        $this->assertGreaterThan(0, $row->id);
        $this->assertTrue(Str::isUuid($row->public_id));
        $this->assertTrue($row->is_active);
        $this->assertNotSame('2000-01-01 00:00:00', $row->created_at->toDateTimeString());
        $audit = AuditEvent::where('action', Region::class.'.created')->sole();
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame((string) $row->id, $audit->resource_id);
        $this->assertNull($audit->region_id);
        $this->assertNull($audit->institution_id);
        $this->assertSame($before + 1, AuditEvent::count());
        $this->evidence('default-and-forged', ['region' => $row->only(['id', 'public_id', 'code', 'name', 'is_active']), 'audit_actor' => $audit->actor_id, 'audit_region_id' => $audit->region_id, 'audit_institution_id' => $audit->institution_id, 'audit_delta' => 1, 'forged_timestamp_ignored' => true]);
    }

    public function test_actual_region_creation_rolls_back_when_mandatory_core_audit_fails(): void
    {
        [$actor, $data] = $this->fixture();
        $count = Region::count();
        $before = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->withArgs(fn ($action, ...$rest) => $action === Region::class.'.created')->andThrow(new RuntimeException('ISOLATED_K5_AUDIT_FAILURE'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $row = Region::where('code', $data['code'])->first();
        $this->evidence('audit_fault', ['regions_before' => $count, 'regions_after' => Region::count(), 'audit_before' => $before, 'audit_after' => AuditEvent::count(), 'persisted_region' => $row?->only(['id', 'public_id', 'code', 'name', 'is_active']), 'sink_reached_once' => true, 'exception_marker' => 'ISOLATED_K5_AUDIT_FAILURE']);
        $this->assertSame($before, AuditEvent::count());
        $this->assertNull($row, 'A failed mandatory Core audit must not leave an unaudited Region.');
    }
}
