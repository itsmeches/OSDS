<?php

namespace Tests\Feature;

use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RegionUpdateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'K4-A', 'name' => 'Disposable K4 before', 'is_active' => true]);
        $outside = Region::create(['code' => 'K4-B', 'name' => 'Disposable K4 outside', 'is_active' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view', 'regions.manage']);
        $grant = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        $this->actingAs($actor);

        return [$actor, $region, $outside, $grant];
    }

    private function values(Region $region): array
    {
        return $region->fresh()->only(['id', 'public_id', 'code', 'name', 'is_active']);
    }

    private function evidence(string $case, array $data): void
    {
        $file = base_path('docs/evidence/phase-3d7-k4-region-update-after.json');
        $all = $case === 'authorized' || ! is_file($file) ? [] : json_decode(file_get_contents($file), true);
        $all[$case] = $data;
        file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    public function test_authorized_actual_edit_persists_exact_fields_and_one_matching_core_audit(): void
    {
        [$actor, $region] = $this->fixture();
        $before = $this->values($region);
        $count = AuditEvent::count();
        $data = ['code' => 'K4-EDITED', 'name' => 'Disposable K4 edited', 'is_active' => false];
        $page = Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id]);
        $this->assertFalse($page->instance()->hasDatabaseTransactions());
        $page->fillForm($data)->call('save', false)->assertHasNoFormErrors()->assertNotified();
        $this->assertSame(array_replace($before, $data), $this->values($region));
        $audit = AuditEvent::where('action', Region::class.'.updated')->where('resource_id', (string) $region->id)->sole();
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame(Region::class, $audit->resource_type);
        $this->assertNull($audit->region_id);
        $this->assertNull($audit->institution_id);
        $this->assertSame('success', $audit->outcome);
        foreach (array_keys($data) as $field) {
            $this->assertContains($field, $audit->metadata['changed_fields']);
        }
        $this->assertSame($count + 1, AuditEvent::count());
        $this->evidence('authorized', ['before' => $before, 'after' => $this->values($region), 'audit_before' => $count, 'audit_after' => AuditEvent::count(), 'audit' => $audit->only(['action', 'resource_type', 'resource_id', 'actor_id', 'region_id', 'institution_id', 'outcome', 'metadata'])]);
    }

    public static function denials(): array
    {
        return [['outside'], ['permission'], ['inactive'], ['revoked']];
    }

    #[DataProvider('denials')]
    public function test_existing_identity_permission_and_scope_denials_leave_region_and_audit_unchanged(string $mode): void
    {
        [$actor, $region, $outside, $grant] = $this->fixture();
        $before = [$this->values($region), $this->values($outside)];
        if ($mode === 'outside') {
            $count = AuditEvent::count();
            Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $outside->id])->assertNotFound();
        } else {
            $page = Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])->fillForm(['name' => 'Must not persist']);
            if ($mode === 'permission') {
                $actor->revokePermissionTo('regions.manage');
            } elseif ($mode === 'inactive') {
                DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);
            } else {
                DB::table('user_region_assignments')->where('id', $grant->id)->update(['revoked_at' => now()]);
            }
            // Component tests retain an authenticated object across requests;
            // use the current identity as the HTTP authentication provider does.
            $this->actingAs($actor->fresh());
            $count = AuditEvent::count();
            $page->call('save', false);
            $mode === 'permission' ? $page->assertForbidden() : $page->assertNotFound();
        }
        $this->assertSame($before, [$this->values($region), $this->values($outside)]);
        $this->assertSame($count, AuditEvent::count());
        $this->evidence('denied-'.$mode, ['before' => $before, 'after' => [$this->values($region), $this->values($outside)], 'status' => $mode === 'permission' ? 403 : 404, 'audit_delta' => 0]);
    }

    public function test_invalid_fields_and_manipulated_protected_state_do_not_bypass_real_form(): void
    {
        [$actor, $region, $outside] = $this->fixture();
        $before = [$this->values($region), $this->values($outside)];
        $count = AuditEvent::count();
        foreach ([['code' => ''], ['code' => str_repeat('X', 33)], ['code' => $outside->code], ['name' => ''], ['name' => str_repeat('X', 256)]] as $invalid) {
            Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])->fillForm($invalid)->call('save', false)->assertHasFormErrors(array_keys($invalid));
            $this->assertSame($before, [$this->values($region), $this->values($outside)]);
            $this->assertSame($count, AuditEvent::count());
        }
        Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])
            ->update(updates: ['data.id' => $outside->id, 'data.public_id' => 'FORGED', 'data.actor_id' => -1, 'data.region_id' => $outside->id], calls: [['method' => 'save', 'params' => [false], 'path' => '']])->assertHasNoFormErrors();
        $this->assertSame($before, [$this->values($region), $this->values($outside)]);
        $this->assertSame($count, AuditEvent::count());
        $this->evidence('input_controls', ['invalid_cases' => 5, 'before' => $before, 'after' => [$this->values($region), $this->values($outside)], 'protected_fields' => 'not persisted; record not rebound', 'audit_delta' => 0]);
    }

    public function test_actual_region_update_rolls_back_when_mandatory_core_audit_fails(): void
    {
        [$actor, $region] = $this->fixture();
        $before = $this->values($region);
        $count = AuditEvent::count();
        $page = Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])->fillForm(['code' => 'K4-FAULT', 'name' => 'Disposable K4 audit fault', 'is_active' => false]);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->withArgs(fn ($action, ...$rest) => $action === Region::class.'.updated')->andThrow(new RuntimeException('ISOLATED_K4_AUDIT_FAILURE'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('save', false)->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $after = $this->values($region);
        $this->evidence('audit_fault', ['before' => $before, 'after' => $after, 'audit_before' => $count, 'audit_after' => AuditEvent::count(), 'sink_reached_once' => true, 'exception_marker' => 'ISOLATED_K4_AUDIT_FAILURE']);
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame($before, $after, 'A failed mandatory Core audit must not leave an unaudited Region update.');
    }
}
