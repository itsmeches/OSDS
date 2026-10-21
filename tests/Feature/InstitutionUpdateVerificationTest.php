<?php

namespace Tests\Feature;

use App\Filament\Resources\Institutions\Pages\EditInstitution;
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

class InstitutionUpdateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $regions = collect(['A', 'B', 'C'])->map(fn ($key) => Region::create(['code' => 'K1-'.$key, 'name' => 'Disposable K1 '.$key]));
        $institution = Institution::create(['name' => 'Disposable K1 before', 'official_id' => 'K1-BEFORE', 'primary_region_id' => $regions[0]->id, 'is_synthetic' => true]);
        $outside = Institution::create(['name' => 'Disposable outside institution', 'primary_region_id' => $regions[2]->id, 'is_synthetic' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
        UserRegionAssignment::withoutEvents(function () use ($actor, $regions) {
            foreach ([$regions[0], $regions[1]] as $region) {
                UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
            }
        });
        $this->actingAs($actor);
        $this->assertTrue($actor->can('update', $institution));
        $this->assertFalse($actor->can('update', $outside));

        return [$actor, $institution, $regions, $outside];
    }

    private function values(Institution $institution): array
    {
        return $institution->fresh()->only(['id', 'name', 'official_id', 'primary_region_id', 'status', 'organization_type', 'is_synthetic']);
    }

    private function evidence(string $case, array $data): void
    {
        $path = base_path('docs/evidence/phase-3d7-k1-institution-update-after.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $all = $case === 'authorized' || ! is_file($path) ? [] : json_decode(file_get_contents($path), true);
        $all[$case] = $data;
        file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    public function test_authorized_actual_edit_persists_exact_fields_and_one_core_audit(): void
    {
        [$actor, $institution, $regions] = $this->fixture();
        $before = $this->values($institution);
        $auditCount = AuditEvent::count();
        Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['name' => 'Disposable K1 edited', 'official_id' => 'K1-EDITED', 'primary_region_id' => $regions[1]->id, 'status' => 'inactive'])
            ->call('save', false)->assertHasNoFormErrors()->assertNotified();
        $after = $this->values($institution);
        $this->assertSame(array_replace($before, ['name' => 'Disposable K1 edited', 'official_id' => 'K1-EDITED', 'primary_region_id' => $regions[1]->id, 'status' => 'inactive']), $after);
        $audit = AuditEvent::where('action', Institution::class.'.updated')->where('resource_id', (string) $institution->id)->sole();
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame($institution->id, $audit->institution_id);
        $this->assertSame($regions[1]->id, $audit->region_id);
        $this->assertSame('success', $audit->outcome);
        $this->assertSame($auditCount + 1, AuditEvent::count());
        $this->evidence('authorized', ['before' => $before, 'after' => $after, 'audit_delta' => 1, 'audit' => $audit->only(['action', 'resource_type', 'resource_id', 'actor_id', 'institution_id', 'region_id', 'outcome', 'metadata'])]);
    }

    public function test_existing_denied_target_and_permission_controls_do_not_mutate(): void
    {
        [$actor, $institution, , $outside] = $this->fixture();
        $before = [$this->values($institution), $this->values($outside), AuditEvent::count()];
        Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $outside->id])->assertNotFound();
        $page = Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id]);
        $actor->revokePermissionTo('institutions.manage');
        $page->call('save', false)->assertForbidden();
        $this->assertSame($before, [$this->values($institution), $this->values($outside), AuditEvent::count()]);
        $this->evidence('denied', ['before' => $before, 'after' => [$this->values($institution), $this->values($outside), AuditEvent::count()], 'outside_status' => 404, 'revoked_permission_status' => 403]);
    }

    public function test_manipulated_destination_and_non_schema_fields_use_existing_form_boundary(): void
    {
        [$actor, $institution, $regions] = $this->fixture();
        $before = $this->values($institution);
        $auditCount = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id]);
        $page->update(updates: ['data.primary_region_id' => $regions[2]->id, 'data.name' => 'Must not partially persist'], calls: [['method' => 'save', 'params' => [false], 'path' => '']])->assertHasFormErrors(['primary_region_id']);
        $this->assertSame($before, $this->values($institution));
        $this->assertSame($auditCount, AuditEvent::count());
        $page->update(updates: ['data.primary_region_id' => $regions[0]->id, 'data.name' => $before['name'], 'data.organization_type' => 'other', 'data.is_synthetic' => false], calls: [['method' => 'save', 'params' => [false], 'path' => '']])->assertHasNoFormErrors();
        $this->assertSame($before, $this->values($institution));
        $this->assertSame($auditCount, AuditEvent::count());
        $this->evidence('manipulated', ['before' => $before, 'after' => $this->values($institution), 'audit_delta' => AuditEvent::count() - $auditCount, 'outside_destination' => 'form validation rejected', 'non_schema_fields' => 'not persisted']);
    }

    public function test_actual_institution_update_rolls_back_when_mandatory_core_audit_fails(): void
    {
        [$actor, $institution, $regions] = $this->fixture();
        $before = $this->values($institution);
        $auditCount = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['name' => 'Disposable K1 audit fault', 'official_id' => 'K1-FAULT', 'primary_region_id' => $regions[1]->id, 'status' => 'inactive']);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->withArgs(fn ($action, ...$rest) => $action === Institution::class.'.updated')
            ->andThrow(new RuntimeException('ISOLATED_K1_AUDIT_FAILURE'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('save', false)->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $after = $this->values($institution);
        $this->evidence('audit_fault', ['before' => $before, 'after' => $after, 'audit_before' => $auditCount, 'audit_after' => AuditEvent::count(), 'exception' => 'ISOLATED_K1_AUDIT_FAILURE']);
        $this->assertSame($auditCount, AuditEvent::count());
        $this->assertSame($before, $after, 'A failed mandatory Core audit must not leave an unaudited institution update.');
    }
}
