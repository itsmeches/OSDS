<?php

namespace Tests\Feature;

use App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages\ListSyntheticApplicationReviewerAssignments;
use App\Models\AuditEvent;
use App\Models\NationwideAccessGrant;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Services\Applications\ApplicationReviewerAssignmentService;
use App\Services\Auditing\AuditRecorder;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationAssignmentAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    private function fixture(string $scope = 'current'): array
    {
        $actor = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $actor->assignRole(AccountRole::Admin->value);
        $this->actingAs($actor);
        $reviewer = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $reviewer->assignRole(AccountRole::Chedro->value);
        $region = Region::create(['public_id' => (string) Str::uuid(), 'code' => 'GA', 'name' => 'Isolated assignment region']);
        $assignment = SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $region->id, 'module_id' => OsdsModule::firstOrFail()->id, 'starts_at' => now()->subDay(), 'reason' => 'Synthetic atomicity fixture']);
        $grant = null;
        if ($scope !== 'none') {
            $attributes = ['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subMinute()->toIso8601String(), 'ends_at' => null, 'granted_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()];
            if ($scope === 'expired') {
                $attributes['starts_at'] = now()->subHour()->toIso8601String();
                $attributes['ends_at'] = now()->subMinute()->toIso8601String();
            }
            if ($scope === 'future') {
                $attributes['starts_at'] = now()->addMinute()->toIso8601String();
            }
            if ($scope === 'revoked') {
                $attributes['revoked_at'] = now()->subSecond()->toIso8601String();
            }
            if ($scope === 'wrong') {
                $attributes['region_id'] = Region::create(['public_id' => (string) Str::uuid(), 'code' => 'GB', 'name' => 'Other region'])->id;
            }
            $grant = DB::table('user_region_assignments')->insertGetId($attributes);
        }

        return [$actor, $assignment->fresh(), $grant];
    }

    public static function deniedScopes(): array
    {
        return array_map(fn ($scope) => [$scope], ['none', 'wrong', 'expired', 'future', 'revoked']);
    }

    #[DataProvider('deniedScopes')]
    public function test_global_management_permission_does_not_require_manager_regional_grants(string $scope): void
    {
        [$actor, $assignment] = $this->fixture($scope);
        $before = [$assignment->getRawOriginal(), AuditEvent::count()];
        Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Global management authority']);
        $this->assertNotNull($assignment->fresh()->revoked_at);
        $this->assertSame($before[1] + 1, AuditEvent::count());
    }

    public function test_success_is_one_attributed_audit_and_duplicate_cannot_mutate_history(): void
    {
        [$actor, $assignment] = $this->fixture();
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Synthetic completed review']);
        $saved = $assignment->fresh();
        $this->assertNotNull($saved->revoked_at);
        $this->assertSame($actor->id, $saved->revoked_by);
        $this->assertSame('Synthetic completed review', $saved->revocation_reason);
        $this->assertLessThan(3, abs(now()->diffInSeconds($saved->revoked_at)));
        $this->assertSame($before + 1, AuditEvent::count());
        $audit = AuditEvent::where('action', 'application.reviewer_assignment_revoked')->sole();
        $this->assertEquals($assignment->id, $audit->resource_id);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame($assignment->region_id, $audit->region_id);
        $original = $saved->getRawOriginal();
        try {
            app(ApplicationReviewerAssignmentService::class)->revoke($actor, $assignment, 'Second request');
            $this->fail('Duplicate revocation must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revocation_reason', $exception->errors());
        }
        $this->assertSame($original, $assignment->fresh()->getRawOriginal());
        $this->assertSame($before + 1, AuditEvent::count());
    }

    public function test_partial_audit_failure_rolls_back_on_same_operation_connection(): void
    {
        [$actor, $assignment] = $this->fixture();
        $before = [$assignment->getRawOriginal(), AuditEvent::count()];
        $level = DB::transactionLevel();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use ($level) {
            $this->assertSame($level + 1, DB::transactionLevel());
            $this->assertSame((new AuditEvent)->getConnection()->getPdo(), (new SyntheticApplicationReviewerAssignment)->getConnection()->getPdo());
            (new AuditRecorder)->record(...$arguments);
            throw new \RuntimeException('Synthetic partial audit failure');
        });
        $page = Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Rollback probe'])
            ->assertNotified('Action could not be confirmed');
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['Synthetic partial audit failure', 'SQLSTATE', $actor->password] as $private) {
            $this->assertStringNotContainsString($private, $response);
        }
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($before, [$assignment->fresh()->getRawOriginal(), AuditEvent::count()]);
    }

    public function test_actual_postgresql_audit_insert_failure_rolls_back_and_redacts_response(): void
    {
        [$actor, $assignment] = $this->fixture('none');
        $before = [$assignment->fresh()->getRawOriginal(), AuditEvent::count()];
        $level = DB::transactionLevel();
        $databaseRejectedAudit = false;
        Log::spy();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use (&$databaseRejectedAudit, $level) {
            $this->assertSame($level + 1, DB::transactionLevel());
            // Corrupt only the downstream test sink's actor foreign key. The
            // authorized action, assignment update and audit insert are real.
            $missingActor = new User;
            $missingActor->id = -1;
            $arguments[2] = $missingActor;
            try {
                return (new AuditRecorder)->record(...$arguments);
            } catch (QueryException $exception) {
                $databaseRejectedAudit = true;
                throw $exception;
            }
        });
        $page = Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Actual database failure probe'])
            ->assertNotified('Action could not be confirmed')->assertHasErrors(['applicationReviewerRevocation']);
        $this->assertTrue($databaseRejectedAudit);
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($before, [$assignment->fresh()->getRawOriginal(), AuditEvent::count()]);
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['SQLSTATE', 'Foreign key violation', $actor->password] as $private) {
            $this->assertStringNotContainsString($private, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed'
            && array_keys($context) === ['support_reference', 'exception_class'] && $context['exception_class'] === QueryException::class);
    }

    public static function revokedAuthority(): array
    {
        return [['permission'], ['scope'], ['expired'], ['inactive']];
    }

    #[DataProvider('revokedAuthority')]
    public function test_revocation_after_mount_is_reauthorized(string $kind): void
    {
        [$actor, $assignment, $grant] = $this->fixture();
        $page = Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)->mountTableAction('revoke', $assignment)->setActionData(['revocation_reason' => 'Stale authority']);
        if ($kind === 'permission') {
            $permissions = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === 'applications.reviewer.assignments.manage')->all();
            $actor->syncRoles([]);
            $actor->syncPermissions($permissions);
        } elseif ($kind === 'inactive') {
            DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);
        } elseif ($kind === 'expired') {
            DB::table('user_region_assignments')->where('id', $grant)->update(['ends_at' => now()->subSecond()->toIso8601String()]);
        } else {
            DB::table('user_region_assignments')->where('id', $grant)->update(['revoked_at' => now()->toIso8601String()]);
        }
        $before = [$assignment->fresh()->getRawOriginal(), AuditEvent::count()];
        if (in_array($kind, ['scope', 'expired'], true)) {
            $page->call('callMountedAction');
            $this->assertNotNull($assignment->fresh()->revoked_at);
            $this->assertSame($before[1] + 1, AuditEvent::count());
        } else {
            $page->call('callMountedAction')->assertForbidden();
            $this->assertSame($before, [$assignment->fresh()->getRawOriginal(), AuditEvent::count()]);
        }
    }

    public function test_effective_nationwide_authority_and_unrelated_history_are_preserved(): void
    {
        [$actor, $assignment] = $this->fixture('none');
        NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subMinute()->toIso8601String(), 'reason' => 'Isolated nationwide test']);
        $other = SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $assignment->reviewer_id, 'region_id' => $assignment->region_id, 'module_id' => $assignment->module_id, 'starts_at' => now(), 'reason' => 'Unrelated immutable fixture']);
        $earlier = AuditEvent::orderBy('id')->get()->map->getRawOriginal()->all();
        $otherState = $other->fresh()->getRawOriginal();
        Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Nationwide authorized']);
        $this->assertNotNull($assignment->fresh()->revoked_at);
        $this->assertSame($otherState, $other->fresh()->getRawOriginal());
        $this->assertSame($earlier, AuditEvent::orderBy('id')->limit(count($earlier))->get()->map->getRawOriginal()->all());
        $this->assertSame('UTC', DB::selectOne('SHOW timezone')->TimeZone);
    }

    public function test_empty_reason_fails_before_update_and_stale_actor_cannot_impersonate(): void
    {
        [$actor, $assignment] = $this->fixture();
        $before = [$assignment->getRawOriginal(), AuditEvent::count()];
        Livewire::actingAs($actor)->test(ListSyntheticApplicationReviewerAssignments::class)
            ->callTableAction('revoke', $assignment, data: ['revocation_reason' => ''])->assertHasActionErrors(['revocation_reason']);
        $this->assertSame($before, [$assignment->fresh()->getRawOriginal(), AuditEvent::count()]);
        auth()->logout();
        $before = [$assignment->fresh()->getRawOriginal(), AuditEvent::count()];
        try {
            app(ApplicationReviewerAssignmentService::class)->revoke($actor, $assignment, 'No authenticated actor');
            $this->fail('Detached actor must not authorize a mutation.');
        } catch (AuthorizationException) {
            $this->assertSame($before, [$assignment->fresh()->getRawOriginal(), AuditEvent::count()]);
        }
    }
}
