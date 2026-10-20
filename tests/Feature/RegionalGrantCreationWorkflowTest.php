<?php

namespace Tests\Feature;

use App\Filament\Resources\UserRegionAssignments\Pages\CreateUserRegionAssignment;
use App\Models\AuditEvent;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RegionalGrantCreationService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegionalGrantCreationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $first = Region::create(['code' => 'G6-A', 'name' => 'Synthetic first region']);
        $region = Region::create(['code' => 'G6-B', 'name' => 'Synthetic target region']);
        $actor = User::factory()->create();
        $actor->assignRole(AccountRole::Admin->value);
        $target = User::factory()->create();
        $target->assignRole(AccountRole::Osds->value);
        $scope = UserRegionAssignment::withoutEvents(function () use ($actor, $target, $first, $region) {
            UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $first->id, 'starts_at' => now()->subDay()]);
            UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $first->id, 'starts_at' => now()->subDay()]);

            return UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
        });
        $data = ['user_id' => $target->id, 'region_id' => $region->id, 'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()];
        $page = Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm($data);

        return [$actor, $target, $scope, $data, $page];
    }

    private function state(): array
    {
        return [UserRegionAssignment::orderBy('id')->get()->toArray(), AuditEvent::count()];
    }

    public function test_success_exact_audit_attributes_and_existing_duplicate_constraint(): void
    {
        [$actor, $target, , $data, $page] = $this->fixture();
        $before = AuditEvent::count();
        $page->call('create')->assertHasNoFormErrors()->assertRedirect();
        $row = UserRegionAssignment::where('user_id', $target->id)->where('region_id', $data['region_id'])->sole();
        $audit = AuditEvent::where('action', UserRegionAssignment::class.'.created')->sole();
        $this->assertSame($actor->id, $row->granted_by);
        $this->assertSame($data['starts_at'], $row->starts_at->toDateTimeString());
        $this->assertSame($data['ends_at'], $row->ends_at->toDateTimeString());
        $this->assertSame($row->region_id, $audit->region_id);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame((string) $row->id, $audit->resource_id);
        $this->assertSame($before + 1, AuditEvent::count());
        $saved = $this->state();
        Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm($data)->call('create')
            ->assertHasFormErrors(['starts_at'])->assertNotified('Action could not be confirmed');
        $this->assertSame($saved, $this->state());
    }

    public static function faults(): array
    {
        return [['before'], ['after'], ['database']];
    }

    #[DataProvider('faults')]
    public function test_audit_failure_is_atomic_and_responses_and_logs_are_safe(string $fault): void
    {
        [$actor, , , $data, $page] = $this->fixture();
        $before = $this->state();
        $level = DB::transactionLevel();
        $inserted = false;
        $rejected = false;
        Log::spy();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($fault, $level, &$inserted, &$rejected) {
            $this->assertSame(UserRegionAssignment::class.'.created', $args[0]);
            $this->assertSame($level + 1, DB::transactionLevel());
            $this->assertSame((new AuditEvent)->getConnection()->getPdo(), (new UserRegionAssignment)->getConnection()->getPdo());
            if ($fault === 'before') {
                throw new \RuntimeException('ISOLATED_REGIONAL_CREATION_FAULT');
            }
            if ($fault === 'database') {
                $missing = new User;
                $missing->id = -1;
                $args[2] = $missing;
                try {
                    return (new AuditRecorder)->record(...$args);
                } catch (QueryException $exception) {
                    $rejected = true;
                    throw $exception;
                }
            }
            (new AuditRecorder)->record(...$args);
            $inserted = true;
            throw new \RuntimeException('ISOLATED_REGIONAL_CREATION_FAULT');
        });
        $page->call('create')->assertHasFormErrors(['starts_at'])->assertNotified('Action could not be confirmed');
        $this->assertSame($fault === 'after', $inserted);
        $this->assertSame($fault === 'database', $rejected);
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($before, $this->state());
        $page->assertFormSet($data);
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['ISOLATED_REGIONAL_CREATION_FAULT', 'SQLSTATE', 'Foreign key violation', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed'
            && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function losses(): array
    {
        return [['permission'], ['panel'], ['inactive'], ['deactivated'], ['revoked'], ['expired'], ['future'], ['target-role']];
    }

    #[DataProvider('losses')]
    public function test_authority_is_rechecked_after_mount_and_in_service(string $loss): void
    {
        [$actor, $target, $scope, $data, $page] = $this->fixture();
        if (in_array($loss, ['permission', 'panel'], true)) {
            $removed = $loss === 'panel' ? 'platform.admin.access' : 'region_scopes.assign';
            $permissions = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === $removed)->all();
            $actor->syncRoles([]);
            $actor->syncPermissions($permissions);
        } elseif ($loss === 'target-role') {
            $target->syncRoles([AccountRole::SuperUser->value]);
        } elseif (in_array($loss, ['inactive', 'deactivated'], true)) {
            DB::table('users')->where('id', $actor->id)->update($loss === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        } else {
            DB::table('user_region_assignments')->where('id', $scope->id)->update(match ($loss) {
                'revoked' => ['revoked_at' => now()],
                'expired' => ['ends_at' => now()->subMinute()],
                'future' => ['starts_at' => now()->addDay()],
            });
        }
        $before = $this->state();
        // Scoped relationship validation can deny a stale selection before the
        // service. Other losses reach execution-time authorization directly.
        $page->call('create');
        if (in_array($loss, ['permission', 'panel', 'inactive', 'deactivated', 'target-role'], true)) {
            $page->assertForbidden();
        }
        $this->assertSame($before, $this->state());
        try {
            app(RegionalGrantCreationService::class)->create($actor, $data);
            $this->fail('Current authority must be required.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->state());
        }
    }

    public static function invalidFields(): array
    {
        return [['user_id', -1], ['region_id', -1], ['starts_at', ''], ['ends_at', '2000-01-01 00:00:00']];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_do_not_mutate(string $field, mixed $value): void
    {
        [, , , $data, $page] = $this->fixture();
        $before = $this->state();
        $page->fillForm([$field => $value])->call('create')->assertHasFormErrors([$field]);
        $this->assertSame($before, $this->state());
        try {
            app(RegionalGrantCreationService::class)->create(auth()->user(), array_replace($data, [$field => $value]));
            $this->fail('Invalid service input must be rejected.');
        } catch (ValidationException) {
            $this->assertSame($before, $this->state());
        }
    }

    public function test_self_role_scope_forgery_and_detached_actor_are_denied(): void
    {
        [$actor, $target, , $data] = $this->fixture();
        $outside = Region::create(['code' => 'G6-OUT', 'name' => 'Outside scope']);
        foreach ([['user_id' => $actor->id], ['region_id' => $outside->id]] as $forged) {
            $before = $this->state();
            Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm(array_replace($data, $forged))->call('create');
            $this->assertSame($before, $this->state());
            try {
                app(RegionalGrantCreationService::class)->create($actor, array_replace($data, $forged));
                $this->fail('Forbidden target must be rejected.');
            } catch (AuthorizationException) {
                $this->assertSame($before, $this->state());
            }
        }
        auth()->login($target);
        try {
            app(RegionalGrantCreationService::class)->create($actor, $data);
            $this->fail('Detached actor must be rejected.');
        } catch (AuthorizationException) {
            $this->assertFalse(UserRegionAssignment::where('user_id', $target->id)->where('region_id', $data['region_id'])->exists());
        }
    }

    public function test_forged_attribution_and_revocation_are_not_persisted(): void
    {
        [$actor, $target, , $data, $page] = $this->fixture();
        $page->set('data.granted_by', $target->id)->set('data.revoked_at', now()->toDateTimeString())->set('data.revoked_by', $target->id)
            ->call('create')->assertHasNoFormErrors();
        $row = UserRegionAssignment::where('user_id', $target->id)->where('region_id', $data['region_id'])->sole();
        $this->assertSame($actor->id, $row->granted_by);
        $this->assertNull($row->revoked_at);
        $this->assertNull($row->revoked_by);
    }

    public function test_direct_create_requires_permission_and_valid_nationwide_scope_still_works(): void
    {
        [$actor, , $scope, $data] = $this->fixture();
        $this->actingAs($actor)->get('/admin/user-region-assignments/create')->assertOk();
        DB::table('user_region_assignments')->where('id', $scope->id)->update(['revoked_at' => now()]);
        NationwideAccessGrant::withoutEvents(fn () => NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated authority fixture']));
        Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $actor->syncRoles([]);
        $this->actingAs($actor->fresh())->get('/admin/user-region-assignments/create')->assertForbidden();
    }

    public function test_expired_unrevoked_grant_still_blocks_duplicate_but_revoked_history_allows_a_new_start(): void
    {
        [$actor, $target, , $data, $page] = $this->fixture();
        $page->call('create')->assertHasNoFormErrors();
        $grant = UserRegionAssignment::where('user_id', $target->id)->where('region_id', $data['region_id'])->sole();
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
        $before = $this->state();
        Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm($data)->call('create')->assertHasFormErrors(['starts_at']);
        $this->assertSame($before, $this->state());
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['revoked_at' => now()]);
        Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(2, UserRegionAssignment::where('user_id', $target->id)->where('region_id', $data['region_id'])->count());
        $this->assertSame(2, AuditEvent::where('action', UserRegionAssignment::class.'.created')->count());
    }
}
