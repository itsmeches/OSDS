<?php

namespace Tests\Feature;

use App\Filament\Pages\SyntheticApplicationIntake;
use App\Models\Application;
use App\Models\ApplicationDecisionStage;
use App\Models\AuditEvent;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationDecisionService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Auditing\AuditRecorder;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\Support\B1Scenario;
use Tests\TestCase;

class ApplicationDecisionFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    private function page(array $s, string $role)
    {
        return Livewire::actingAs($s['staff'][$role])->withQueryParams(['application' => $s['case']->public_id])->test(SyntheticApplicationIntake::class);
    }

    private function act(array $s, string $role, string $action, array $extra = []): ApplicationDecisionStage
    {
        $this->actingAs($s['staff'][$role]);
        $case = $s['case']->fresh();

        return app(ApplicationDecisionService::class)->act($s['staff'][$role], $case, $case->current_submission_id, $case->lock_version,
            $case->decisionStages()->where('submission_id', $case->current_submission_id)->max('id'), (string) Str::uuid(), $action,
            $extra + ['summary' => 'Public synthetic '.$action, 'internal_notes' => 'PRIVATE_B1_NOTES', 'attested' => true]);
    }

    public function test_actual_three_actor_livewire_workflow_approves_exact_revision_and_hides_internal_notes(): void
    {
        $s = B1Scenario::create();
        $hash = $s['submission']->combined_hash;
        $before = AuditEvent::count();
        $this->page($s, 'evaluator')->set('decisionSummary', 'Public evaluation complete')->set('decisionNotes', 'PRIVATE_B1_NOTES')->set('decisionAttested', true)
            ->call('recordDecisionStage', 'evaluated')->assertHasNoErrors();
        $this->page($s, 'chief')->set('decisionSummary', 'Public recommendation')->call('recordDecisionStage', 'recommended')->assertHasNoErrors();
        $this->page($s, 'director')->set('decisionSummary', 'Public approved outcome')->call('recordDecisionStage', 'approved')->assertHasNoErrors();
        $case = $s['case']->fresh();
        $this->assertSame('approved', $case->state);
        $this->assertSame(3, $case->decisionStages()->count());
        $this->assertSame($before + 3, AuditEvent::count());
        $this->assertSame($hash, $s['submission']->fresh()->combined_hash);
        $this->assertSame($s['region']->id, $case->routing_region_id);
        $this->assertSame($case->final_decision_id, $case->decisionStages()->where('action', 'approved')->sole()->id);
        $this->actingAs($s['applicant'])->get(route('applicant.applications.show', $case))->assertOk()->assertSee('Public approved outcome')->assertDontSee('PRIVATE_B1_NOTES');
    }

    public function test_director_can_reject_after_recommendation(): void
    {
        $s = B1Scenario::create('acsa');
        $this->act($s, 'evaluator', 'evaluated');
        $this->act($s, 'chief', 'recommended');
        $this->page($s, 'director')->set('decisionSummary', 'Public rejection reason')->call('recordDecisionStage', 'rejected')->assertHasNoErrors();
        $this->assertSame('rejected', $s['case']->fresh()->state);
    }

    private function denied(callable $operation, string $class = ValidationException::class): void
    {
        try {
            $operation();
            $this->fail('The forbidden action was accepted.');
        } catch (\Throwable $error) {
            $this->assertInstanceOf($class, $error);
        }
    }

    public function test_chief_return_requires_reevaluation_and_case_wide_separation_survives_supersession(): void
    {
        $s = B1Scenario::create();
        $this->denied(fn () => $this->act($s, 'chief', 'recommended'));
        $this->denied(fn () => $this->act($s, 'director', 'approved'));
        $this->act($s, 'evaluator', 'evaluated');
        $this->act($s, 'chief', 'returned');
        $this->denied(fn () => $this->act($s, 'director', 'approved'));
        $s['staff']['evaluator']->givePermissionTo(['applications.recommendations.record', 'applications.decisions.record']);
        $this->act($s, 'evaluator', 'evaluated');
        $this->denied(fn () => $this->act($s, 'evaluator', 'recommended'), AuthorizationException::class);
        $this->act($s, 'chief', 'recommended');
        $this->denied(fn () => $this->act($s, 'evaluator', 'approved'), AuthorizationException::class);
        $s['staff']['chief']->givePermissionTo('applications.decisions.record');
        $this->denied(fn () => $this->act($s, 'chief', 'rejected'), AuthorizationException::class);
        $this->act($s, 'director', 'approved');
        $this->assertSame(5, $s['case']->decisionStages()->count());
    }

    public function test_correction_is_one_operation_and_new_revision_needs_new_stages_without_forgetting_actor_roles(): void
    {
        $s = B1Scenario::create();
        $this->act($s, 'evaluator', 'evaluated');
        $this->act($s, 'chief', 'recommended');
        $beforeHistory = $s['case']->history()->count();
        $this->page($s, 'evaluator')->set('correctionSummary', 'Please correct the synthetic name')->set('correctionInstruction', 'Correct the name')
            ->call('requestCorrection')->assertHasNoErrors();
        $case = $s['case']->fresh();
        $this->assertSame('correction_requested', $case->state);
        $this->assertSame($beforeHistory + 1, $case->history()->count());
        $this->assertSame(1, $case->correctionRequests()->count());
        $this->assertSame(1, $case->decisionStages()->where('action', 'correction_requested')->count());
        $this->denied(fn () => $this->act($s, 'director', 'approved'));
        $oldHash = $s['submission']->combined_hash;
        $this->actingAs($s['applicant']);
        $working = $case->currentWorkingRevision;
        app(ApplicationLifecycleService::class)->submit($s['applicant'], $case, $case->lock_version, $working->draft->lock_version, (string) Str::uuid());
        app(ApplicationLifecycleService::class)->beginScreening($s['staff']['evaluator'], $case->fresh());
        $this->assertSame($oldHash, $s['submission']->fresh()->combined_hash);
        $this->assertNotSame($s['submission']->id, $case->fresh()->current_submission_id);
        $this->denied(fn () => $this->act($s, 'chief', 'recommended'));
        $s['staff']['contender']->givePermissionTo('applications.evaluation.record');
        $this->act($s, 'contender', 'evaluated');
        $s['staff']['evaluator']->givePermissionTo(['applications.recommendations.record', 'applications.decisions.record']);
        $this->denied(fn () => $this->act($s, 'evaluator', 'recommended'), AuthorizationException::class);
        $this->act($s, 'chief', 'recommended');
        $this->denied(fn () => $this->act($s, 'evaluator', 'approved'), AuthorizationException::class);
        $this->act($s, 'director', 'approved');
    }

    public function test_mounted_stale_revision_version_missing_attestation_and_changed_request_key_are_denied(): void
    {
        $s = B1Scenario::create();
        $stale = $this->page($s, 'evaluator');
        $this->page($s, 'evaluator')->set('decisionSummary', 'Evaluation')->call('recordDecisionStage', 'evaluated')->assertHasErrors('decision');
        $this->act($s, 'evaluator', 'evaluated');
        $stale->set('decisionSummary', 'Stale')->set('decisionAttested', true)->call('recordDecisionStage', 'evaluated')->assertHasErrors('decision');
        $this->assertSame(1, ApplicationDecisionStage::count());
        $this->actingAs($s['staff']['chief']);
        $case = $s['case']->fresh();
        $key = (string) Str::uuid();
        $service = app(ApplicationDecisionService::class);
        $args = [$s['staff']['chief'], $case, $case->current_submission_id, $case->lock_version, ApplicationDecisionStage::max('id'), $key, 'recommended'];
        $first = $service->act(...[...$args, ['summary' => 'Same request']]);
        $this->assertSame($first->id, $service->act(...[...$args, ['summary' => 'Same request']])->id);
        $this->denied(fn () => $service->act(...[...$args, ['summary' => 'Different payload']]));
        $args[2] = $case->current_submission_id + 999;
        $this->denied(fn () => $service->act(...[...$args, ['summary' => 'Same request']]));
        $this->assertSame(2, ApplicationDecisionStage::count());
    }

    public function test_fresh_permission_identity_assignment_and_region_boundaries(): void
    {
        $s = B1Scenario::create();
        $actor = $s['staff']['evaluator'];
        $page = $this->page($s, 'evaluator');
        $actor->revokePermissionTo('applications.evaluation.record');
        $page->set('decisionSummary', 'Denied')->set('decisionAttested', true)->call('recordDecisionStage', 'evaluated')->assertForbidden();
        $actor->givePermissionTo('applications.evaluation.record');
        foreach ([['status', 'inactive', 'active'], ['is_synthetic', false, true], ['deactivated_at', now(), null]] as [$field, $value, $restore]) {
            DB::table('users')->where('id', $actor->id)->update([$field => $value]);
            $this->denied(fn () => $this->act($s, 'evaluator', 'evaluated'), AuthorizationException::class);
            DB::table('users')->where('id', $actor->id)->update([$field => $restore]);
        }
        $assignment = SyntheticApplicationReviewerAssignment::where('reviewer_id', $actor->id)->firstOrFail();
        foreach ([now()->subYears(2), now()->addYears(2)] as $clock) {
            $this->travelTo($clock);
            $this->denied(fn () => $this->act($s, 'evaluator', 'evaluated'), AuthorizationException::class);
            $this->travelBack();
        }
        $grant = $actor->regionAssignments()->firstOrFail();
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['ends_at' => now()->subHour()]);
        $this->denied(fn () => $this->act($s, 'evaluator', 'evaluated'), AuthorizationException::class);
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['ends_at' => null]);
        $assignment->update(['revoked_at' => now(), 'revoked_by' => $s['publisher']->id, 'revocation_reason' => 'Disposable test revocation']);
        $this->denied(fn () => $this->act($s, 'evaluator', 'evaluated'), AuthorizationException::class);
        $this->assertSame(0, ApplicationDecisionStage::count());
    }

    public function test_all_stage_audit_failures_leave_exact_operation_state_and_outer_transaction_clean(): void
    {
        $s = B1Scenario::create();
        $real = new AuditRecorder;
        foreach (['evaluated' => 'evaluator', 'recommended' => 'chief', 'approved' => 'director'] as $action => $role) {
            foreach (['before', 'after', 'postgres'] as $mode) {
                $before = [$s['case']->fresh()->getRawOriginal(), ApplicationDecisionStage::count(), AuditEvent::count(), $s['case']->history()->count()];
                $outerDepth = DB::transactionLevel();
                $pdo = DB::connection()->getPdo();
                $mock = Mockery::mock(AuditRecorder::class);
                $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($mode, $real, $outerDepth, $pdo) {
                    $this->assertGreaterThan($outerDepth, DB::transactionLevel());
                    $this->assertSame($pdo, DB::connection()->getPdo());
                    if ($mode === 'postgres') {
                        $args[2] = (new User)->forceFill(['id' => 999999999]);

                        return $real->record(...$args);
                    }
                    if ($mode === 'after') {
                        $real->record(...$args);
                    }
                    throw new RuntimeException('PRIVATE_B1_AUDIT_FAULT');
                });
                app()->instance(AuditRecorder::class, $mock);
                $this->page($s, $role)->set('decisionSummary', 'Failure control')->set('decisionAttested', true)->call('recordDecisionStage', $action)->assertHasErrors('staffAction');
                $this->assertSame($before, [$s['case']->fresh()->getRawOriginal(), ApplicationDecisionStage::count(), AuditEvent::count(), $s['case']->history()->count()]);
                $this->assertSame($outerDepth, DB::transactionLevel());
                app()->instance(AuditRecorder::class, $real);
            }
            $this->act($s, $role, $action);
        }
    }

    public function test_b1_correction_audit_failure_rolls_back_existing_correction_and_working_copy(): void
    {
        $s = B1Scenario::create();
        $before = [$s['case']->fresh()->getRawOriginal(), $s['case']->history()->count(), DB::table('application_drafts')->count(), DB::table('application_working_revisions')->count(), AuditEvent::count()];
        $real = new AuditRecorder;
        $mock = Mockery::mock(AuditRecorder::class);
        $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($real) {
            $real->record(...$args);
            throw new RuntimeException('PRIVATE_B1_CORRECTION_FAULT');
        });
        app()->instance(AuditRecorder::class, $mock);
        $page = $this->page($s, 'evaluator')->set('correctionSummary', 'Public instruction')->set('correctionInstruction', 'Correct the synthetic name')->call('requestCorrection')->assertHasErrors('staffAction');
        $transport = json_encode([$page->snapshot, $page->effects, $page->html(false)]);
        $this->assertStringNotContainsString('PRIVATE_B1_CORRECTION_FAULT', $transport);
        $this->assertSame($before, [$s['case']->fresh()->getRawOriginal(), $s['case']->history()->count(), DB::table('application_drafts')->count(), DB::table('application_working_revisions')->count(), AuditEvent::count()]);
        $this->assertSame(0, $s['case']->correctionRequests()->count());
        $this->assertSame(0, ApplicationDecisionStage::count());
    }

    public function test_postgres_rejects_unbound_final_states_orphan_decisions_mutation_and_duplicate_terminal_cases(): void
    {
        $s = B1Scenario::create();
        $this->denied(fn () => DB::transaction(fn () => DB::table('applications')->where('id', $s['case']->id)->update(['state' => 'approved', 'lock_version' => $s['case']->lock_version + 1])), QueryException::class);
        $evaluation = $this->act($s, 'evaluator', 'evaluated');
        $this->denied(fn () => DB::transaction(fn () => ApplicationDecisionStage::create(['application_id' => $s['case']->id, 'submission_id' => $s['submission']->id, 'actor_id' => $s['staff']['evaluator']->id, 'stage_role' => 'chief', 'action' => 'recommended', 'predecessor_id' => $evaluation->id, 'applicant_summary' => 'Same actor denied by PostgreSQL', 'request_key' => (string) Str::uuid(), 'payload_hash' => str_repeat('b', 64)])), QueryException::class);
        $recommendation = $this->act($s, 'chief', 'recommended');
        $this->denied(fn () => DB::transaction(function () use ($s, $recommendation) {
            ApplicationDecisionStage::create(['application_id' => $s['case']->id, 'submission_id' => $s['submission']->id, 'actor_id' => $s['staff']['director']->id, 'stage_role' => 'director', 'action' => 'approved', 'predecessor_id' => $recommendation->id, 'applicant_summary' => 'Orphan', 'request_key' => (string) Str::uuid(), 'payload_hash' => str_repeat('a', 64)]);
            DB::statement('SET CONSTRAINTS b1_final_commit IMMEDIATE');
        }), QueryException::class);
        $this->denied(fn () => DB::transaction(fn () => $evaluation->update(['internal_notes' => 'overwrite'])), QueryException::class);
        $this->act($s, 'director', 'approved');
        $case = $s['case']->fresh();
        $this->denied(fn () => DB::transaction(fn () => $case->update(['state' => 'rejected', 'lock_version' => $case->lock_version + 1])), QueryException::class);
        $this->denied(fn () => DB::transaction(fn () => Application::create(['institution_id' => $case->institution_id, 'module_id' => $case->module_id, 'publication_manifest_id' => $case->publication_manifest_id, 'applicant_id' => $case->applicant_id, 'state' => 'draft', 'lock_version' => 1])), QueryException::class);
        $this->denied(fn () => app(ApplicantDraftService::class)->start($s['applicant'], $s['institution'], $s['manifest']));
        $this->assertSame('approved', $case->fresh()->state);
        $this->assertSame(1, ApplicationDecisionStage::where('stage_role', 'director')->count());
    }

    public function test_wrong_region_module_and_detached_identity_cannot_record_stages(): void
    {
        $s = B1Scenario::create();
        $outside = B1Scenario::create('acsa');
        $actor = $outside['staff']['evaluator'];
        $s['staff']['outsider'] = $actor;
        $this->denied(fn () => $this->act($s, 'outsider', 'evaluated'), AuthorizationException::class);
        // Same region, wrong module still has no exact-pair assignment.
        auth()->logout();
        UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $s['region']->id, 'starts_at' => now()->subDay()]);
        SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $actor->id, 'region_id' => $s['region']->id, 'module_id' => $outside['module']->id, 'starts_at' => now()->subDay(), 'granted_by' => $s['publisher']->id, 'reason' => 'Disposable wrong-module fixture']);
        $this->denied(fn () => $this->act($s, 'outsider', 'evaluated'), AuthorizationException::class);
        auth()->logout();
        $case = $s['case']->fresh();
        $this->denied(fn () => app(ApplicationDecisionService::class)->act($s['staff']['evaluator'], $case, $case->current_submission_id, $case->lock_version, null, (string) Str::uuid(), 'evaluated', ['summary' => 'Detached', 'attested' => true]), AuthorizationException::class);
        $this->assertSame(0, ApplicationDecisionStage::count());
    }
}
