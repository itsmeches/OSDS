<?php

namespace Tests\Feature;

use App\Filament\Pages\IssuanceDefinitions;
use App\Livewire\Applicant\IssuanceRequests;
use App\Models\AuditEvent;
use App\Models\IssuanceDefinition;
use App\Models\IssuanceRequest;
use App\Models\User;
use App\Services\Applications\ApplicationDecisionService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Auditing\AuditRecorder;
use App\Services\Documents\CaseIssuanceService;
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
use Tests\Support\B2Scenario;
use Tests\TestCase;

class CaseIssuanceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    private function save(array $s, ?string $key = null, array $answers = ['contact_note' => 'Fictional contact'], int $version = 0, bool $submit = true): IssuanceRequest
    {
        $this->actingAs($s['applicant']);

        return app(CaseIssuanceService::class)->persist($s['applicant'], $s['case'], $s['definition']->id, $version, $key ?? (string) Str::uuid(), $answers, $submit);
    }

    private function denied(callable $call, string $type = ValidationException::class): void
    {
        try {
            $call();
            $this->fail('Forbidden operation accepted.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf($type, $e);
        }
    }

    public function test_actual_osds_definition_review_publication_and_applicant_cor_draft_submission(): void
    {
        $s = B2Scenario::create(publish: false);
        $page = Livewire::actingAs($s['manager'])->test(IssuanceDefinitions::class)->set('titleInput', $s['input']['title'])->set('instructions', $s['input']['instructions'])->set('approvalId', $s['approval']->id)->set('sourceFormId', $s['input']['source_form_id'])->set('nameQuestionId', $s['question']->id)->set('addressQuestionId', $s['addressQuestion']->id)->call('createDefinition')->assertHasNoErrors();
        $def = IssuanceDefinition::sole();
        $page->set('previewId', $def->id)->assertSee('Preview only. No request or certificate is created.')->call('advanceDefinition', $def->id, 1, 'reviewed')->assertHasNoErrors()->call('advanceDefinition', $def->id, 2, 'published')->assertHasNoErrors();
        $this->assertSame(0, IssuanceRequest::count());
        $before = AuditEvent::count();
        $ui = Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->assertSee('123 Fictional Demo Road')->set('answers.contact_note', 'Fictional contact')->call('save', false)->assertHasNoErrors();
        $draft = IssuanceRequest::sole();
        $this->assertSame('draft', $draft->state);
        $ui->call('save', true)->assertHasNoErrors()->assertSee('Demonstration request submitted. No certificate has been generated.');
        $row = $draft->fresh();
        $this->assertSame('submitted', $row->state);
        $this->assertSame('cor', $row->certificate_type);
        $this->assertSame($s['case']->final_decision_id, $row->decision_id);
        $this->assertSame($s['submission']->id, $row->submission_id);
        $this->assertSame('123 Fictional Demo Road', $row->identity_snapshot['address']);
        $this->assertSame($before + 2, AuditEvent::count());
        $this->assertSame(2, DB::table('issuance_history')->where('request_id', $row->id)->count());
    }

    public function test_acsa_only_approval_is_sufficient_and_idempotent_retry_rechecks_authority(): void
    {
        $s = B2Scenario::create('acsa');
        $key = (string) Str::uuid();
        $row = $this->save($s, $key);
        $this->assertSame('acsa', $row->certificate_type);
        $this->assertSame($row->id, $this->save($s, $key)->id);
        $this->denied(fn () => $this->save($s, $key, ['contact_note' => 'Different']));
        $this->denied(fn () => $this->save($s));
        $s['applicant']->revokePermissionTo('issuance.requests.submit');
        $this->denied(fn () => $this->save($s, $key), AuthorizationException::class);
        $this->assertSame(1, IssuanceRequest::count());
    }

    public function test_unapproved_and_rejected_cases_missing_definition_and_missing_bindings_deny(): void
    {
        foreach (['screening', 'rejected'] as $outcome) {
            $s = B2Scenario::create(outcome: $outcome);
            $this->denied(fn () => $this->save($s));
        }
        $s = B2Scenario::create(publish: false);
        $this->actingAs($s['manager']);
        $input = $s['input'];
        $input['address_question_id'] = 999999;
        $this->denied(fn () => app(CaseIssuanceService::class)->createDefinition($s['manager'], $input));
        $this->assertSame(0, IssuanceRequest::count());
    }

    public function test_forged_protected_fields_stale_drafts_and_withdrawn_publications_are_rejected(): void
    {
        $s = B2Scenario::create();
        $this->denied(fn () => $this->save($s, answers: ['contact_note' => 'Okay', 'institution_id' => 999]));
        $key = (string) Str::uuid();
        $draft = $this->save($s, $key, submit: false);
        $this->denied(fn () => $this->save($s, $key));
        $this->actingAs($s['manager']);
        app(CaseIssuanceService::class)->transition($s['manager'], $s['definition'], $s['definition']->lock_version, 'withdrawn');
        $this->denied(fn () => $this->save($s, $key, version: $draft->lock_version));
        $this->assertSame('draft', $draft->fresh()->state);
        $this->assertSame(0, IssuanceRequest::where('state', 'submitted')->count());
    }

    public function test_current_representation_ownership_permission_and_staff_access_denials(): void
    {
        $s = B2Scenario::create();
        $other = B2Scenario::create('acsa');
        $this->actingAs($other['applicant']);
        $this->get(route('applicant.issuance-requests', ['application' => $s['case']->public_id]))->assertNotFound();
        $this->actingAs($s['staff']['evaluator']);
        Livewire::test(IssuanceDefinitions::class)->assertForbidden();
        DB::table('institution_representatives')->where('user_id', $s['applicant']->id)->update(['revoked_at' => now(), 'revoked_by' => $s['publisher']->id]);
        $this->denied(fn () => $this->save($s), AuthorizationException::class);
        $this->assertSame(0, IssuanceRequest::count());
    }

    public function test_real_audit_insert_failure_and_postgres_rejection_roll_back_request_history_and_draft(): void
    {
        $s = B2Scenario::create();
        $real = new AuditRecorder;
        foreach (['before', 'after', 'postgres'] as $mode) {
            $before = [IssuanceRequest::count(), AuditEvent::count(), DB::table('issuance_history')->count()];
            $depth = DB::transactionLevel();
            $pdo = DB::connection()->getPdo();
            $mock = Mockery::mock(AuditRecorder::class);
            $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($real, $mode, $depth, $pdo) {
                $this->assertGreaterThan($depth, DB::transactionLevel());
                $this->assertSame($pdo, DB::connection()->getPdo());
                if ($mode === 'postgres') {
                    $args[2] = (new User)->forceFill(['id' => 999999999]);

                    return $real->record(...$args);
                }if ($mode === 'after') {
                    $real->record(...$args);
                }throw new \RuntimeException('PRIVATE_B2_FAULT');
            });
            app()->instance(AuditRecorder::class, $mock);
            $ui = Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->set('answers.contact_note', 'Fictional')->call('save', true)->assertHasErrors('issuance');
            $this->assertStringNotContainsString('PRIVATE_B2_FAULT', json_encode([$ui->snapshot, $ui->effects, $ui->html(false)]));
            $this->assertSame($before, [IssuanceRequest::count(), AuditEvent::count(), DB::table('issuance_history')->count()]);
            $this->assertSame($depth, DB::transactionLevel());
            app()->instance(AuditRecorder::class, $real);
        }
    }

    public function test_postgres_immutable_snapshot_and_cross_record_constraints(): void
    {
        $s = B2Scenario::create();
        $row = $this->save($s);
        $original = $row->fresh()->getRawOriginal();
        $this->denied(fn () => DB::transaction(fn () => $row->update(['answers' => ['contact_note' => 'Overwrite'], 'lock_version' => $row->lock_version + 1])), QueryException::class);
        $clone = $original;
        unset($clone['id']);
        $clone['public_id'] = (string) Str::uuid();
        $clone['request_key'] = (string) Str::uuid();
        $clone['module_id'] = 999999;
        $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_requests')->insert($clone)), QueryException::class);
        $clone['module_id'] = $row->module_id;
        $clone['submission_id'] = 999999;
        $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_requests')->insert($clone)), QueryException::class);
        $clone['submission_id'] = $row->submission_id;
        $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_requests')->insert($clone)), QueryException::class);
        $this->assertSame($original, $row->fresh()->getRawOriginal());
        $this->assertSame(1, IssuanceRequest::count());
    }

    public function test_missing_resolved_identity_and_unpublished_definition_block_actual_submission(): void
    {
        $s = B2Scenario::create(publish: false);
        $service = app(CaseIssuanceService::class);
        $this->actingAs($s['applicant']);
        Livewire::withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->assertSee('OSDS must publish an issuance definition');
        $this->actingAs($s['manager']);
        $input = $s['input'];
        $input['address_question_id'] = $s['unansweredQuestion']->id;
        $definition = $service->createDefinition($s['manager'], $input);
        $definition = $service->transition($s['manager'], $definition, 1, 'reviewed');
        $definition = $service->transition($s['manager'], $definition, 2, 'published');
        $s['definition'] = $definition;
        $this->denied(fn () => $this->save($s));
        $this->assertSame(0, IssuanceRequest::count());
        Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->assertSee('approved address answer cannot be resolved');
    }

    public function test_draft_recovery_uses_explicit_new_version_and_submitted_snapshot_survives_withdrawal(): void
    {
        $s = B2Scenario::create();
        $old = $this->save($s, submit: false);
        $service = app(CaseIssuanceService::class);
        $this->actingAs($s['manager']);
        $service->transition($s['manager'], $s['definition'], 3, 'withdrawn');
        $new = $service->createDefinition($s['manager'], $s['input']);
        $new = $service->transition($s['manager'], $new, 1, 'reviewed');
        $new = $service->transition($s['manager'], $new, 2, 'published');
        $s['definition'] = $new;
        $key = (string) Str::uuid();
        $row = $this->save($s, $key);
        $this->assertSame('draft', $old->fresh()->state);
        $this->assertSame(2, IssuanceRequest::count());
        $this->actingAs($s['manager']);
        $service->transition($s['manager'], $new, 3, 'withdrawn');
        $this->assertSame($row->id, $this->save($s, $key)->id);
        Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->assertSee('No certificate has been generated.')->assertSee('Fictional contact');
    }

    public function test_draft_and_definition_audit_failure_are_atomic_and_staff_authority_is_fresh(): void
    {
        $s = B2Scenario::create();
        $draft = $this->save($s, submit: false);
        $before = [$draft->fresh()->getRawOriginal(), AuditEvent::count(), DB::table('issuance_history')->count()];
        $real = new AuditRecorder;
        $mock = Mockery::mock(AuditRecorder::class);
        $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($real) {
            $real->record(...$args);
            throw new \RuntimeException('PRIVATE_B2_SAVED_DRAFT_FAULT');
        });
        app()->instance(AuditRecorder::class, $mock);
        $this->denied(fn () => $this->save($s, $draft->request_key, version: $draft->lock_version), \RuntimeException::class);
        $this->assertSame($before, [$draft->fresh()->getRawOriginal(), AuditEvent::count(), DB::table('issuance_history')->count()]);
        app()->instance(AuditRecorder::class, $real);
        $this->actingAs($s['manager']);
        $before = [IssuanceDefinition::count(), AuditEvent::count(), DB::table('issuance_history')->count()];
        $mock = Mockery::mock(AuditRecorder::class);
        $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('PRIVATE_B2_DEFINITION_FAULT'));
        app()->instance(AuditRecorder::class, $mock);
        $this->denied(fn () => app(CaseIssuanceService::class)->createDefinition($s['manager'], $s['input']), \RuntimeException::class);
        $this->assertSame($before, [IssuanceDefinition::count(), AuditEvent::count(), DB::table('issuance_history')->count()]);
        app()->instance(AuditRecorder::class, $real);
        $s['manager']->revokePermissionTo('issuance.definitions.publish');
        $this->denied(fn () => app(CaseIssuanceService::class)->transition($s['manager'], $s['definition'], 3, 'withdrawn'), AuthorizationException::class);
    }

    public function test_postgres_checks_wrong_routing_institution_module_and_exact_decision_and_history(): void
    {
        $s = B2Scenario::create();
        $row = $this->save($s);
        $baseline = $row->fresh()->getRawOriginal();
        foreach (['submission_id', 'decision_id', 'institution_id', 'region_id', 'module_id', 'actor_id'] as $field) {
            $copy = $baseline;
            unset($copy['id']);
            $copy['public_id'] = (string) Str::uuid();
            $copy['request_key'] = (string) Str::uuid();
            $copy[$field] = 999999999;
            $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_requests')->insert($copy)), QueryException::class);
        }
        $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_history')->where('request_id', $row->id)->update(['event' => 'overwrite'])), QueryException::class);
        $this->assertSame($baseline, $row->fresh()->getRawOriginal());
        $this->assertSame(1, IssuanceRequest::count());
    }

    public function test_no_qualifying_case_account_status_and_cross_module_definition_denials(): void
    {
        $u = User::factory()->create(['is_synthetic' => true]);
        $u->givePermissionTo('applicant.portal.access');
        $this->actingAs($u)->get(route('applicant.issuance-requests'))->assertOk()->assertSee('No accessible COR application.')->assertSee('No accessible ACSA application.');
        $s = B2Scenario::create();
        $other = B2Scenario::create('acsa');
        $s['definition'] = $other['definition'];
        $this->denied(fn () => $this->save($s));
        $s['definition'] = IssuanceDefinition::where('module_id', $s['module']->id)->where('state', 'published')->firstOrFail();
        DB::table('users')->where('id', $s['applicant']->id)->update(['status' => 'inactive']);
        $this->denied(fn () => $this->save($s), AuthorizationException::class);
        DB::table('users')->where('id', $s['applicant']->id)->update(['status' => 'active', 'is_synthetic' => false]);
        $this->denied(fn () => $this->save($s), AuthorizationException::class);
        $this->assertSame(0, IssuanceRequest::count());
    }

    public function test_configuration_types_conditions_and_published_immutability(): void
    {
        $s = B2Scenario::create();
        $service = app(CaseIssuanceService::class);
        $definition = ['questions' => [['key' => 'yes', 'type' => 'yes_no', 'label' => 'Yes or no', 'required' => true, 'condition' => null], ['key' => 'note', 'type' => 'short_text', 'label' => 'Conditional note', 'required' => true, 'condition' => ['question' => 'yes', 'operator' => 'equals', 'value' => 'yes']]]];
        $this->assertSame(['yes' => 'no'], $service->answers($definition, ['yes' => 'no', 'note' => 'Hidden ignored'], true));
        $this->denied(fn () => $service->answers($definition, ['yes' => 'yes'], true));
        $this->denied(fn () => $service->answers($definition, ['yes' => 'forged'], true));
        $this->actingAs($s['manager']);
        $this->denied(fn () => $service->transition($s['manager'], $s['definition'], 1, 'withdrawn'));
        $this->denied(fn () => DB::transaction(fn () => $s['definition']->update(['instructions' => 'Overwrite', 'lock_version' => 4])), QueryException::class);
        $this->assertSame('published', $s['definition']->fresh()->state);
    }

    public function test_issuance_uses_revised_approved_submission_and_rejects_real_superseded_copy(): void
    {
        $s = B2Scenario::create(outcome: 'screening');
        $oldHash = $s['submission']->combined_hash;
        $this->actingAs($s['staff']['evaluator']);
        $case = $s['case']->fresh();
        app(ApplicationDecisionService::class)->act($s['staff']['evaluator'], $case, $case->current_submission_id, $case->lock_version, null, (string) Str::uuid(), 'correction_requested', ['summary' => 'Review synthetic contact details', 'items' => [['target_kind' => 'general', 'issue_category' => 'clarification', 'instruction' => 'Confirm synthetic contact details']]]);
        $case = $case->fresh();
        $this->actingAs($s['applicant']);
        app(ApplicationLifecycleService::class)->submit($s['applicant'], $case, $case->lock_version, $case->currentWorkingRevision->draft->lock_version, (string) Str::uuid());
        app(ApplicationLifecycleService::class)->beginScreening($s['staff']['evaluator'], $case->fresh());
        foreach (['evaluator' => 'evaluated', 'chief' => 'recommended', 'director' => 'approved'] as $role => $action) {
            $this->actingAs($s['staff'][$role]);
            $case = $case->fresh();
            app(ApplicationDecisionService::class)->act($s['staff'][$role], $case, $case->current_submission_id, $case->lock_version, $case->decisionStages()->where('submission_id', $case->current_submission_id)->max('id'), (string) Str::uuid(), $action, ['summary' => 'B2 revised synthetic stage', 'attested' => true]);
        }
        $s['case'] = $case->fresh();
        $row = $this->save($s);
        $this->assertNotSame($s['submission']->id, $row->submission_id);
        $this->assertSame($s['case']->current_submission_id, $row->submission_id);
        $copy = $row->fresh()->getRawOriginal();
        unset($copy['id']);
        $copy['public_id'] = (string) Str::uuid();
        $copy['request_key'] = (string) Str::uuid();
        $copy['submission_id'] = $s['submission']->id;
        $this->denied(fn () => DB::transaction(fn () => DB::table('issuance_requests')->insert($copy)), QueryException::class);
        $this->assertSame($oldHash, $s['submission']->fresh()->combined_hash);
        $this->assertSame(1, IssuanceRequest::count());
    }

    public function test_postgres_rejects_forged_protected_json_snapshots_with_valid_foreign_keys(): void
    {
        $s = B2Scenario::create();
        $row = $this->save($s);
        $baseline = $row->fresh()->getRawOriginal();
        foreach (['organization' => 'Forged name', 'address' => 'Forged address', 'submission_id' => 999999, 'director_actor_id' => 999999, 'submission_hash' => str_repeat('f', 64), 'evidence_revision_ids' => []] as $field => $value) {
            $copy = $baseline;
            unset($copy['id']);
            $copy['public_id'] = (string) Str::uuid();
            $copy['request_key'] = (string) Str::uuid();
            $identity = $row->identity_snapshot;
            $identity[$field] = $value;
            $copy['identity_snapshot'] = json_encode($identity);
            try {
                DB::transaction(fn () => DB::table('issuance_requests')->insert($copy));
                $this->fail('Forged snapshot accepted.');
            } catch (QueryException $e) {
                $this->assertStringContainsString('Approved issuance snapshot bindings', $e->getMessage());
            }
        }
        $this->assertSame($baseline, $row->fresh()->getRawOriginal());
        $this->assertSame(1, IssuanceRequest::count());
    }

    public function test_optional_declaration_multiple_choice_defaults_and_approved_evidence_requirements(): void
    {
        $s = B2Scenario::create(fieldEdges: true, evidenceMinimum: 1);
        Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)->assertSet('answers.activities', [])->assertSet('answers.confirm', false)->set('answers.contact_note', 'Fictional edge fields')->call('save', false)->assertHasNoErrors()->call('save', true)->assertHasNoErrors()->assertSee('None selected')->assertSee('Not confirmed');
        $row = IssuanceRequest::sole();
        $this->assertSame([], $row->answers['activities']);
        $this->assertFalse($row->answers['confirm']);
        $this->assertCount(1, $row->identity_snapshot['evidence_revision_ids']);
        $s = B2Scenario::create(evidenceMinimum: 2);
        $this->denied(fn () => $this->save($s));
        $this->assertSame(1, IssuanceRequest::where('state', 'submitted')->count());
    }
}
