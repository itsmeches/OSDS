<?php

namespace Tests\Feature;

use App\Filament\Resources\Regions\Pages\CreateRegion;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RegionCreationService;
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

class RegionCreateRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view', 'regions.manage']);
        $this->actingAs($actor);
        $data = ['code' => 'K5R', 'name' => 'Disposable K5R region', 'is_active' => false];
        $page = Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data);

        return [$actor, $data, $page];
    }

    public static function faults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('faults')]
    public function test_audit_insert_then_failure_or_genuine_database_rejection_rolls_back(string $mode): void
    {
        [$actor, $data, $page] = $this->fixture();
        $count = AuditEvent::count();
        $depth = DB::transactionLevel();
        $pdo = DB::connection()->getPdo();
        Log::spy();
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($mode, $depth, $pdo) {
            $this->assertSame(Region::class.'.created', $args[0]);
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
            throw new RuntimeException('ISOLATED_K5_PARTIAL_AUDIT');
        });
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['name'])->assertNotified('Action could not be confirmed');
        $this->assertSame(0, Region::count());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame($depth, DB::transactionLevel());
        $this->assertSame($data['name'], $page->get('data.name'));
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)]);
        foreach (['ISOLATED_K5_PARTIAL_AUDIT', 'SQLSTATE', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed' && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function changes(): array
    {
        return [['permission'], ['panel'], ['inactive'], ['deactivated']];
    }

    #[DataProvider('changes')]
    public function test_stale_mounted_identity_does_not_authorize_creation(string $change): void
    {
        [$actor, $data, $page] = $this->fixture();
        if (in_array($change, ['permission', 'panel'])) {
            $actor->revokePermissionTo($change === 'permission' ? 'regions.manage' : 'platform.admin.access');
        } else {
            DB::table('users')->where('id', $actor->id)->update($change === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        }
        $before = AuditEvent::count();
        // Keep the old authenticated object; the service must reread identity.
        $page->call('create')->assertForbidden();
        try {
            app(RegionCreationService::class)->create($actor, $data);
            $this->fail('Stale authority must not create a Region.');
        } catch (AuthorizationException) {
            $this->assertSame(0, Region::count());
            $this->assertSame($before, AuditEvent::count());
        }
    }

    public function test_removing_existing_role_membership_after_mount_denies_creation(): void
    {
        [$actor, $data] = $this->fixture();
        $actor->assignRole('Admin');
        $actor->revokePermissionTo('regions.manage');
        $page = Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm($data);
        $actor->removeRole('Admin');
        $before = AuditEvent::count();
        $page->call('create')->assertForbidden();
        $this->assertSame(0, Region::count());
        $this->assertSame($before, AuditEvent::count());
    }

    public function test_separate_valid_creates_keep_uuid_uniqueness_and_do_not_create_authority_relationships(): void
    {
        [$actor, $data] = $this->fixture();
        $before = AuditEvent::count();
        foreach (['A', 'B'] as $suffix) {
            Livewire::actingAs($actor)->test(CreateRegion::class)->fillForm(array_replace($data, ['code' => 'K5R-'.$suffix]))->call('create')->assertHasNoFormErrors()->assertRedirect();
        }
        $this->assertSame(2, Region::count());
        $this->assertSame(2, Region::pluck('public_id')->unique()->count());
        $this->assertSame($before + 2, AuditEvent::count());
        $this->assertSame(0, UserRegionAssignment::count());
        $this->assertSame(0, Institution::count());
        $this->assertSame(0, $actor->nationwideAccessGrants()->count());
        foreach (AuditEvent::where('action', Region::class.'.created')->get() as $audit) {
            $this->assertSame($actor->id, $audit->actor_id);
            $this->assertNull($audit->region_id);
            $this->assertNull($audit->institution_id);
            $this->assertSame([], $audit->metadata['changed_fields']);
        }
    }

    public function test_detached_actor_is_denied_by_authoritative_service(): void
    {
        [, $data] = $this->fixture();
        $other = User::factory()->create(['is_synthetic' => true]);
        $other->givePermissionTo(['platform.admin.access', 'regions.manage']);
        $before = AuditEvent::count();
        try {
            app(RegionCreationService::class)->create($other, $data);
            $this->fail('Detached identity must be denied.');
        } catch (AuthorizationException) {
            $this->assertSame(0, Region::count());
            $this->assertSame($before, AuditEvent::count());
        }
    }
}
