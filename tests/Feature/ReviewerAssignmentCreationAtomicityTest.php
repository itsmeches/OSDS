<?php

namespace Tests\Feature;

use App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages\CreateSyntheticApplicationReviewerAssignment;
use App\Models\AuditEvent;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Services\Applications\ApplicationReviewerCreationService;
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
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewerAssignmentCreationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
        $actor = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $actor->assignRole(AccountRole::Admin->value);
        $reviewer = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $reviewer->assignRole(AccountRole::Chedro->value);
        $region = Region::create(['code' => 'GD', 'name' => 'Isolated creation region']);
        $data = ['reviewer_id' => $reviewer->id, 'region_id' => $region->id, 'module_id' => OsdsModule::firstOrFail()->id,
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'), 'ends_at' => now()->addDay()->format('Y-m-d H:i:s'), 'reason' => 'Synthetic grant'];
        $page = Livewire::actingAs($actor)->test(CreateSyntheticApplicationReviewerAssignment::class)->fillForm($data);

        return [$actor, $reviewer, $data, $page];
    }

    public function test_success_attributes_one_audit_and_separate_duplicate_calls_retain_existing_semantics(): void
    {
        [$actor, $reviewer, $data, $page] = $this->fixture();
        $before = AuditEvent::count();
        $page->call('create')->assertHasNoFormErrors()->assertRedirect();
        $saved = SyntheticApplicationReviewerAssignment::sole();
        $this->assertSame($actor->id, $saved->granted_by);
        $this->assertSame($reviewer->id, $saved->reviewer_id);
        $this->assertSame($data['reason'], $saved->reason);
        $this->assertSame($data['ends_at'], $saved->ends_at->format('Y-m-d H:i:s'));
        $audit = AuditEvent::where('action', 'application.reviewer_assignment_granted')->sole();
        $this->assertSame((string) $saved->id, $audit->resource_id);
        $this->assertSame($actor->id, $audit->actor_id);
        $this->assertSame($before + 1, AuditEvent::count());
        Livewire::actingAs($actor)->test(CreateSyntheticApplicationReviewerAssignment::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(2, SyntheticApplicationReviewerAssignment::count());
        $this->assertSame(2, AuditEvent::where('action', 'application.reviewer_assignment_granted')->count());
    }

    public static function auditFaults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('auditFaults')]
    public function test_post_insert_and_genuine_database_audit_failures_roll_back_and_redact(string $fault): void
    {
        [$actor, , , $page] = $this->fixture();
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        $level = DB::transactionLevel();
        $rejected = false;
        Log::spy();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($fault, $level, &$rejected) {
            $this->assertSame($level + 1, DB::transactionLevel());
            $this->assertSame((new AuditEvent)->getConnection()->getPdo(), (new SyntheticApplicationReviewerAssignment)->getConnection()->getPdo());
            $this->assertSame(1, SyntheticApplicationReviewerAssignment::count());
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
            throw new \RuntimeException('ISOLATED_CREATION_AUDIT_FAILURE');
        });
        $page->call('create')->assertHasFormErrors(['reason'])->assertNotified('Action could not be confirmed');
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($before, [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()]);
        $this->assertSame($fault === 'database', $rejected);
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['ISOLATED_CREATION_AUDIT_FAILURE', 'SQLSTATE', 'Foreign key violation', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed'
            && array_keys($context) === ['support_reference', 'exception_class']);
        $page->assertFormSet(['reason' => 'Synthetic grant']);
    }

    public static function lostAuthority(): array
    {
        return [['permission'], ['panel'], ['inactive'], ['sandbox']];
    }

    #[DataProvider('lostAuthority')]
    public function test_current_authority_is_required_after_mount_and_by_service(string $loss): void
    {
        [$actor, , $data, $page] = $this->fixture();
        if (in_array($loss, ['permission', 'panel'], true)) {
            $removed = $loss === 'panel' ? 'platform.admin.access' : 'applications.reviewer.assignments.manage';
            $permissions = $actor->getAllPermissions()->pluck('name')->reject(fn ($name) => $name === $removed)->all();
            $actor->syncRoles([]);
            $actor->syncPermissions($permissions);
        } elseif ($loss === 'inactive') {
            DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);
        } else {
            app()->detectEnvironment(fn () => 'production');
        }
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        $page->call('create')->assertForbidden();
        try {
            app(ApplicationReviewerCreationService::class)->create($actor, $data);
            $this->fail('Stale actor must not authorize creation.');
        } catch (AuthorizationException) {
            $this->assertSame($before, [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()]);
        }
    }

    public static function invalidInputs(): array
    {
        return [['reason', ''], ['region_id', -1], ['module_id', -1], ['reviewer_id', -1], ['ends_at', '2000-01-01 00:00:00']];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_has_no_effect(string $field, mixed $value): void
    {
        [, , , $page] = $this->fixture();
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        $page->fillForm([$field => $value])->call('create')->assertHasFormErrors([$field]);
        $this->assertSame($before, [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()]);
    }

    public function test_forged_attribution_is_ignored_and_nonsynthetic_reviewer_is_denied(): void
    {
        [$actor, $reviewer, $data, $page] = $this->fixture();
        $page->set('data.granted_by', $reviewer->id)->set('data.revoked_at', now()->toIso8601String())
            ->set('data.public_id', (string) Str::uuid())->call('create')->assertHasNoFormErrors();
        $saved = SyntheticApplicationReviewerAssignment::sole();
        $this->assertSame($actor->id, $saved->granted_by);
        $this->assertNull($saved->revoked_at);
        $this->assertNotSame($page->get('data.public_id'), $saved->public_id);
        $real = User::factory()->create(['is_synthetic' => false]);
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        try {
            app(ApplicationReviewerCreationService::class)->create($actor, [...$data, 'reviewer_id' => $real->id]);
            $this->fail('Non-synthetic reviewer must be denied.');
        } catch (AuthorizationException) {
            $this->assertSame($before, [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()]);
        }
    }

    public function test_direct_create_route_and_detached_actor_do_not_grant_authority(): void
    {
        [$actor, , $data] = $this->fixture();
        $other = User::factory()->create(['is_synthetic' => true, 'status' => 'active']);
        $other->assignRole(AccountRole::Chedro->value);
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        $this->actingAs($other)->get('/admin/synthetic-application-reviewer-assignments/create')->assertForbidden();
        try {
            app(ApplicationReviewerCreationService::class)->create($actor, $data);
            $this->fail('An actor different from the authenticated identity must be denied.');
        } catch (AuthorizationException) {
            $this->assertSame($before, [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()]);
        }
    }

    public function test_application_assignment_creation_rolls_back_when_mandatory_audit_fails(): void
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
        $actor = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $actor->assignRole(AccountRole::Admin->value);
        $reviewer = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $reviewer->assignRole(AccountRole::Chedro->value);
        $region = Region::create(['public_id' => (string) Str::uuid(), 'code' => 'GC', 'name' => 'Isolated creation region']);
        $page = Livewire::actingAs($actor)->test(CreateSyntheticApplicationReviewerAssignment::class)
            ->fillForm([
                'reviewer_id' => $reviewer->id,
                'region_id' => $region->id,
                'module_id' => OsdsModule::firstOrFail()->id,
                'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'ends_at' => null,
                'reason' => 'Isolated creation atomicity probe',
            ])->assertHasNoFormErrors();
        $before = [SyntheticApplicationReviewerAssignment::count(), AuditEvent::count()];
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()
            ->withArgs(fn ($action, ...$arguments) => $action === 'application.reviewer_assignment_granted')
            ->andThrow(new \RuntimeException('Isolated creation audit fault'));
        $page->call('create')->assertHasFormErrors(['reason'])
            ->assertNotified('Action could not be confirmed');
        $this->assertSame($before[1], AuditEvent::count());
        $this->assertSame($before[0], SyntheticApplicationReviewerAssignment::count(), 'A failed mandatory grant audit must not leave a reviewer assignment persisted.');
    }
}
