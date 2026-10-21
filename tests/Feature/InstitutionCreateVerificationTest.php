<?php

namespace Tests\Feature;

use App\Filament\Resources\Institutions\Pages\CreateInstitution;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InstitutionCreateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'K3-A', 'name' => 'Disposable K3 allowed']);
        $outside = Region::create(['code' => 'K3-B', 'name' => 'Disposable K3 outside']);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
        UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        $this->actingAs($actor);

        return [$actor, ['name' => 'Disposable K3 institution', 'official_id' => 'K3-CONTROL', 'primary_region_id' => $region->id, 'status' => 'inactive'], $outside];
    }

    private function evidence(string $case, array $data): void
    {
        $file = base_path('docs/evidence/phase-3d7-k3-institution-create-after.json');
        $all = $case === 'authorized' || ! is_file($file) ? [] : json_decode(file_get_contents($file), true);
        $all[$case] = $data;
        file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    public function test_authorized_actual_create_persists_fields_and_one_matching_core_audit(): void
    {
        [$actor, $data] = $this->fixture();
        $before = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data);
        $this->assertFalse($page->instance()->hasDatabaseTransactions());
        $page->call('create')->assertHasNoFormErrors()->assertRedirect();
        $row = Institution::sole();
        $this->assertSame($data, $row->only(array_keys($data)));
        $this->assertSame('hei', $row->organization_type);
        $this->assertFalse($row->is_synthetic);
        $this->assertNotEmpty($row->public_id);
        $audit = AuditEvent::where('action', Institution::class.'.created')->where('resource_id', (string) $row->id)->sole();
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame($row->id, $audit->institution_id);
        $this->assertSame($row->primary_region_id, $audit->region_id);
        $this->assertSame('success', $audit->outcome);
        $this->assertSame($before + 1, AuditEvent::count());
        $this->evidence('authorized', ['institution' => $row->only(['id', 'public_id', ...array_keys($data), 'organization_type', 'is_synthetic']), 'audit_before' => $before, 'audit_after' => AuditEvent::count(), 'audit' => $audit->only(['action', 'resource_type', 'resource_id', 'actor_id', 'institution_id', 'region_id', 'outcome', 'metadata'])]);
    }

    public function test_permission_and_inactive_identity_are_denied_without_creation(): void
    {
        [$actor, $data] = $this->fixture();
        $page = Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data);
        $actor->revokePermissionTo('institutions.manage');
        $before = AuditEvent::count();
        $page->call('create')->assertForbidden();
        $this->assertSame(0, Institution::count());
        $this->assertSame($before, AuditEvent::count());
        $actor->givePermissionTo('institutions.manage');
        $actor->update(['status' => 'inactive']);
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(CreateInstitution::class)->assertForbidden();
        $this->assertSame(0, Institution::count());
        $this->assertSame($before, AuditEvent::count());
        $this->evidence('denied', ['permission_status' => 403, 'inactive_status' => 403, 'institutions' => 0, 'action_audit_delta' => 0]);
    }

    public function test_invalid_scope_fields_and_forged_protected_attributes_use_real_form(): void
    {
        [$actor, $data, $outside] = $this->fixture();
        $before = AuditEvent::count();
        foreach ([['primary_region_id' => $outside->id], ['primary_region_id' => 999999], ['name' => ''], ['name' => str_repeat('x', 256)], ['status' => 'forged']] as $invalid) {
            Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm(array_replace($data, $invalid))->call('create')->assertHasFormErrors(array_keys($invalid));
            $this->assertSame(0, Institution::count());
            $this->assertSame($before, AuditEvent::count());
        }
        $page = Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data);
        $page->update(updates: ['data.is_synthetic' => true, 'data.organization_type' => 'other', 'data.public_id' => 'FORGED', 'data.actor_id' => 999999], calls: [['method' => 'create', 'params' => [], 'path' => '']])->assertHasNoFormErrors()->assertRedirect();
        $row = Institution::sole();
        $this->assertFalse($row->is_synthetic);
        $this->assertSame('hei', $row->organization_type);
        $this->assertNotSame('FORGED', $row->public_id);
        $this->assertSame($actor->id, AuditEvent::where('action', Institution::class.'.created')->sole()->actor_id);
        $this->assertSame($before + 1, AuditEvent::count());
        Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data)->call('create')->assertHasFormErrors(['official_id']);
        $this->assertSame(1, Institution::count());
        $this->assertSame($before + 1, AuditEvent::count());
        $this->evidence('input_controls', ['invalid_cases' => 5, 'rejected_case_institution_delta' => 0, 'rejected_case_audit_delta' => 0, 'duplicate_official_id' => 'validation rejected', 'forged_state' => $row->only(['organization_type', 'is_synthetic', 'public_id']), 'audit_actor' => $actor->id]);
    }

    public function test_actual_institution_creation_rolls_back_when_mandatory_core_audit_fails(): void
    {
        [$actor, $data] = $this->fixture();
        $before = AuditEvent::count();
        $count = Institution::count();
        $page = Livewire::actingAs($actor)->test(CreateInstitution::class)->fillForm($data);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->withArgs(fn ($action, ...$rest) => $action === Institution::class.'.created')->andThrow(new RuntimeException('ISOLATED_K3_AUDIT_FAILURE'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $row = Institution::where('official_id', $data['official_id'])->first();
        $this->evidence('audit_fault', ['institutions_before' => $count, 'institutions_after' => Institution::count(), 'audit_before' => $before, 'audit_after' => AuditEvent::count(), 'persisted_institution' => $row?->only(['id', ...array_keys($data), 'organization_type', 'is_synthetic']), 'expected_sink_reached' => true, 'exception_marker' => 'ISOLATED_K3_AUDIT_FAILURE']);
        $this->assertSame($before, AuditEvent::count());
        $this->assertNull($row, 'A failed mandatory Core audit must not leave an unaudited institution.');
    }
}
