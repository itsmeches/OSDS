<?php

namespace Tests\Feature;

use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Models\AuditEvent;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RegionUpdateService;
use App\Services\Authorization\ScopeAuthorizer;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RegionUpdateRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'K4R', 'name' => 'Disposable K4R original', 'is_active' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view', 'regions.manage']);
        $grant = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        $this->actingAs($actor);
        $data = ['code' => 'K4R-EDITED', 'name' => 'Disposable K4R edited', 'is_active' => false];
        $page = Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])->fillForm($data);

        return [$actor, $region, $grant, $data, $page];
    }

    public static function faults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('faults')]
    public function test_actual_audit_insert_and_database_rejection_roll_back_entire_operation(string $mode): void
    {
        [$actor, $region, , $data, $page] = $this->fixture();
        $before = $region->fresh()->getRawOriginal();
        $count = AuditEvent::count();
        $depth = DB::transactionLevel();
        $pdo = DB::connection()->getPdo();
        Log::spy();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($mode, $depth, $pdo) {
            $this->assertSame(Region::class.'.updated', $args[0]);
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
            throw new RuntimeException('ISOLATED_K4_PARTIAL_AUDIT');
        });
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('save', false)->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $this->assertSame($before, $region->fresh()->getRawOriginal());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame($depth, DB::transactionLevel());
        $this->assertSame($data['name'], $page->get('data.name'));
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)]);
        foreach (['ISOLATED_K4_PARTIAL_AUDIT', 'SQLSTATE', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed' && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function changes(): array
    {
        return array_map(fn ($v) => [$v], ['permission', 'panel', 'inactive', 'deactivated', 'revoked', 'expired', 'future']);
    }

    #[DataProvider('changes')]
    public function test_mounted_component_and_service_require_current_authority(string $change): void
    {
        [$actor, $region, $grant, $data, $page] = $this->fixture();
        if (in_array($change, ['permission', 'panel'])) {
            $actor->revokePermissionTo($change === 'permission' ? 'regions.manage' : 'platform.admin.access');
        } elseif (in_array($change, ['inactive', 'deactivated'])) {
            DB::table('users')->where('id', $actor->id)->update($change === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        } else {
            DB::table('user_region_assignments')->where('id', $grant->id)->update(match ($change) {
                'revoked' => ['revoked_at' => now()],
                'expired' => ['ends_at' => now()->subMinute()],
                'future' => ['starts_at' => now()->addHour()],
            });
        }
        $before = $region->fresh()->getRawOriginal();
        $count = AuditEvent::count();
        // Deliberately retain the mounted authenticated object here; the
        // operation itself must read current identity rather than that cache.
        $page->call('save', false);
        if (in_array($change, ['revoked', 'expired', 'future'])) {
            $page->assertNotFound();
        } else {
            $page->assertForbidden();
        }
        try {
            app(RegionUpdateService::class)->update($actor, $region, $data);
            $this->fail('Current authority must be required.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $region->fresh()->getRawOriginal());
            $this->assertSame($count, AuditEvent::count());
        }
    }

    public function test_noop_repeated_explicit_updates_activity_uuid_and_null_audit_binding_are_preserved(): void
    {
        [$actor, $region, , $data, $page] = $this->fixture();
        $uuid = $region->public_id;
        $before = AuditEvent::count();
        $page->call('save', false)->assertHasNoFormErrors();
        $page->call('save', false)->assertHasNoFormErrors();
        $this->assertSame($before + 1, AuditEvent::count());
        $this->assertSame($uuid, $region->fresh()->public_id);
        $this->assertFalse($region->fresh()->is_active);
        $this->assertTrue(app(ScopeAuthorizer::class)->canAccessRegion($actor->fresh(), $region->id));
        $audit = AuditEvent::where('action', Region::class.'.updated')->sole();
        $this->assertNull($audit->region_id);
        $this->assertNull($audit->institution_id);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertEqualsCanonicalizing(array_keys($data), array_values(array_diff($audit->metadata['changed_fields'], ['updated_at'])));
        $page->fillForm(['name' => 'Second explicit authorized writer'])->call('save', false)->assertHasNoFormErrors();
        $this->assertSame('Second explicit authorized writer', $region->fresh()->name);
        $this->assertSame($before + 2, AuditEvent::count());
    }

    public function test_existing_nationwide_authority_remains_an_alternative(): void
    {
        [$actor, $region, $grant, $data] = $this->fixture();
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['revoked_at' => now()]);
        NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Disposable K4 nationwide fixture']);
        Livewire::actingAs($actor)->test(EditRegion::class, ['record' => $region->id])->fillForm($data)->call('save', false)->assertHasNoFormErrors();
        $this->assertSame($data, $region->fresh()->only(array_keys($data)));
    }

    public function test_valid_update_cannot_forge_identity_scope_or_audit_attribution(): void
    {
        [$actor, $region, , , $page] = $this->fixture();
        $uuid = $region->public_id;
        $before = AuditEvent::count();
        $page->update(updates: ['data.id' => -1, 'data.public_id' => 'FORGED', 'data.actor_id' => -1, 'data.region_id' => -1, 'data.institution_id' => -1], calls: [['method' => 'save', 'params' => [false], 'path' => '']])->assertHasNoFormErrors();
        $this->assertSame($uuid, $region->fresh()->public_id);
        $this->assertSame($before + 1, AuditEvent::count());
        $audit = AuditEvent::where('action', Region::class.'.updated')->sole();
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame((string) $region->id, $audit->resource_id);
        $this->assertNull($audit->region_id);
        $this->assertNull($audit->institution_id);
        $this->assertSame('Disposable K4R edited', $region->fresh()->name);
    }

    public function test_detached_identity_and_invalid_service_fields_are_rejected(): void
    {
        [$actor, $region, , $data] = $this->fixture();
        $other = User::factory()->create(['is_synthetic' => true]);
        $other->givePermissionTo(['platform.admin.access', 'regions.manage']);
        $before = $region->fresh()->getRawOriginal();
        $count = AuditEvent::count();
        try {
            app(RegionUpdateService::class)->update($other, $region, $data);
            $this->fail('Detached actor must be denied.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $region->fresh()->getRawOriginal());
        }
        try {
            app(RegionUpdateService::class)->update($actor, $region, array_replace($data, ['is_active' => 'invalid']));
            $this->fail('Existing boolean field contract must hold.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_active', $exception->errors());
        }
        $this->assertSame($before, $region->fresh()->getRawOriginal());
        $this->assertSame($count, AuditEvent::count());
    }
}
