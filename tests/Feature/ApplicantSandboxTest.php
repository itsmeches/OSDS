<?php

namespace Tests\Feature;

use App\Filament\Pages\SyntheticApplicationIntake;
use App\Filament\Pages\SyntheticEvidenceReviews;
use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\ListSyntheticEvidenceReviewerAssignments;
use App\Livewire\Applicant\DraftEditor;
use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\AuditEvent;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\EvidenceReference;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\PublicationManifestRetirement;
use App\Models\Region;
use App\Models\RequirementSetVersion;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\SyntheticEvidenceReviewFinding;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ReviewerQueueScope;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Authorization\StaffListScope;
use App\Services\Evidence\EvidenceLocatorCipher;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\GoogleEvidenceLocatorParser;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Carbon\CarbonImmutable;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\DevelopmentFixtureSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportComputed\CannotCallComputedDirectlyException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class ApplicantSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    public function test_admin_can_open_applicant_sandbox_eligibility_grant_form(): void
    {
        $admin = $this->user(AccountRole::Admin, true);

        $this->actingAs($admin)
            ->get('/admin/applicant-sandbox-eligibility-grants/create')
            ->assertOk()
            ->assertSee('Synthetic organization')
            ->assertSee('Questionnaire service');
    }

    public function test_development_fixture_provisions_repeatable_manual_testing_publications(): void
    {
        putenv('OSDS_FIXTURE_PASSWORD=SyntheticFixturePassword123!');
        $_ENV['OSDS_FIXTURE_PASSWORD'] = 'SyntheticFixturePassword123!';
        $_SERVER['OSDS_FIXTURE_PASSWORD'] = 'SyntheticFixturePassword123!';

        $this->seed(DevelopmentFixtureSeeder::class);
        $this->seed(DevelopmentFixtureSeeder::class);

        $this->assertSame(2, PublicationManifest::query()->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->count());
        $this->assertSame(2, ApplicantSandboxEligibilityGrant::query()->whereNull('revoked_at')->count());
        $this->assertSame(2, FormVersion::query()->count());
        $this->assertDatabaseHas('form_questions', ['key' => 'organization_email', 'is_required' => false]);
        $this->assertDatabaseHas('form_questions', ['key' => 'private_organization_details', 'is_required' => true]);
        $this->assertDatabaseHas('documentary_requirements', ['key' => 'additional_supporting_document', 'is_required' => false]);
    }

    public function test_verified_synthetic_school_starts_saves_and_resumes_manifest_bound_draft(): void
    {
        [$school,$institution,$manifest,$question] = $this->scenario();
        $service = app(ApplicantDraftService::class);
        $this->assertTrue($school->is_synthetic);
        $this->assertTrue($school->can('applications.draft.create'));
        $this->assertTrue($institution->mayUseApplicantSandbox());
        $this->assertTrue(app(ApplicantSandboxGuard::class)->allowed());
        $draft = $service->start($school, $institution, $manifest);
        $version = $service->save($school, $draft, ['name' => 'Synthetic Association'], 1, (string) Str::uuid());
        $this->assertSame(2, $version);
        $this->assertSame([], $service->missing($draft->fresh()));
        $this->actingAs($school)->get(route('applicant.home'))->assertOk()->assertSee('Synthetic Association', false)->assertSee('Resume draft');
        $this->actingAs($school)->get(route('applicant.drafts.edit', $draft))->assertOk()->assertSee($question->label);
    }

    public function test_school_can_complete_and_submit_through_the_livewire_editor(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $requirement = $manifest->requirementSetVersion->requirements()->firstOrFail();

        Livewire::actingAs($school)
            ->test(DraftEditor::class, ['draft' => $draft])
            ->set("answers.{$question->id}", 'Synthetic Association')
            ->assertSet('saveState', 'Unsaved changes')
            ->call('save')
            ->assertSet('saveState', 'Saved')
            ->set('section', $manifest->formVersion->sections->count())
            ->assertSee('How to add a document')
            ->set("evidenceInputs.{$requirement->id}", 'https://docs.google.com/document/')
            ->call('addEvidence', $requirement->id)
            ->assertSet("evidenceInputs.{$requirement->id}", 'https://docs.google.com/document/')
            ->assertSet("evidenceStates.{$requirement->id}", 'Link not added')
            ->assertSet("evidenceErrors.{$requirement->id}", 'Enter an individual Google Drive, Docs, Sheets, or Slides file link.')
            ->assertSet('saveState', 'Saved')
            ->set("evidenceInputs.{$requirement->id}", 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view')
            ->call('addEvidence', $requirement->id)
            ->assertSet('saveState', 'Saved')
            ->assertSet("evidenceStates.{$requirement->id}", 'Link added')
            ->call('checkProgress')
            ->assertSet('missing', [])
            ->assertSet('documentMissing', [])
            ->call('submitTestApplication')
            ->assertRedirect();

        $this->assertDatabaseHas('applications', ['applicant_id' => $school->id, 'state' => 'submitted']);
        $this->assertDatabaseCount('application_submissions', 1);
    }

    public function test_incomplete_submission_guides_the_applicant_to_the_blocking_step(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);

        Livewire::actingAs($school)
            ->test(DraftEditor::class, ['draft' => $draft])
            ->set('section', $manifest->formVersion->sections->count() + 1)
            ->call('submitTestApplication')
            ->assertSet('section', 0)
            ->assertSet('missing', [$question->label])
            ->assertHasErrors('progress');

        app(ApplicantDraftService::class)->save(
            $school,
            $draft->fresh(),
            ['name' => 'Synthetic Association'],
            1,
            (string) Str::uuid(),
        );

        Livewire::actingAs($school)
            ->test(DraftEditor::class, ['draft' => $draft->fresh()])
            ->set('section', $manifest->formVersion->sections->count() + 1)
            ->call('submitTestApplication')
            ->assertSet('section', $manifest->formVersion->sections->count())
            ->assertSet('missing', [])
            ->assertSet('documentMissing', [$manifest->requirementSetVersion->requirements()->firstOrFail()->label])
            ->assertHasErrors('progress');

        $this->assertDatabaseCount('application_submissions', 0);
    }

    public function test_stale_and_duplicate_saves_do_not_overwrite_or_increment_twice(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $service = app(ApplicantDraftService::class);
        $draft = $service->start($school, $institution, $manifest);
        $key = (string) Str::uuid();
        $this->assertSame(2, $service->save($school, $draft, ['name' => 'First'], 1, $key));
        $this->assertSame(2, $service->save($school, $draft, ['name' => 'Ignored retry'], 1, $key));
        try {
            $service->save($school, $draft, ['name' => 'Stale'], 1, (string) Str::uuid());
            $this->fail('Stale save succeeded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('conflict', $e->errors());
        }
        $this->assertSame('First', $draft->fresh()->answers()->first()->value);
    }

    public function test_only_synthetic_school_identity_can_sign_in_to_applicant_portal(): void
    {
        $password = 'Synthetic-password-123';
        $synthetic = $this->user(AccountRole::School, true);
        $synthetic->update(['password' => $password]);
        $this->post(route('applicant.login.store'), ['email' => $synthetic->email, 'password' => $password])->assertRedirect(route('applicant.home'));
        auth()->logout();
        $regular = $this->user(AccountRole::School, false);
        $regular->update(['password' => $password]);
        $this->post(route('applicant.login.store'), ['email' => $regular->email, 'password' => $password])
            ->assertSessionHasErrors(['email' => 'This account is not enabled for the synthetic applicant sandbox.']);

        $pending = $this->user(AccountRole::School, true);
        $pending->update(['password' => $password, 'status' => 'pending_verification']);
        $this->post(route('applicant.login.store'), ['email' => $pending->email, 'password' => $password])
            ->assertSessionHasErrors(['email' => 'This SCHOOL account is awaiting verification by another authorized CHEDRO reviewer.']);
    }

    public function test_retired_manifest_preserves_view_but_blocks_edit_and_new_drafts(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $service = app(ApplicantDraftService::class);
        $draft = $service->start($school, $institution, $manifest);
        PublicationManifestRetirement::query()->create(['publication_manifest_id' => $manifest->id, 'retired_by' => $manifest->published_by, 'reason' => 'Synthetic retirement', 'correlation_id' => (string) Str::uuid(), 'retired_at' => now()]);
        $this->actingAs($school)->get(route('applicant.drafts.edit', $draft))->assertOk()->assertSee('editing is unavailable');
        $this->expectException(ValidationException::class);
        $service->save($school, $draft, ['name' => 'Blocked'], 1, (string) Str::uuid());
    }

    public function test_wrong_user_and_non_synthetic_identity_are_denied(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $other = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $other->assignRole(AccountRole::School->value);
        $this->actingAs($other)->get(route('applicant.drafts.edit', $draft))->assertNotFound();
        $school->update(['is_synthetic' => false]);
        $this->actingAs($school)->get(route('applicant.home'))->assertForbidden();
    }

    public function test_review_publication_does_not_become_applicant_publication(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form,$requirements] = $this->configuration($osds);
        $service = app(QuestionnairePublicationService::class);
        $approval = $service->review($admin, $service->submitForReview($osds, $form, $requirements), true, 'APP-SBX');
        $review = $service->publishSandbox($admin, $approval);
        $this->assertSame('review_sandbox', $review->channel);
        $this->assertDatabaseMissing('publication_manifests', ['channel' => 'applicant_sandbox']);
    }

    public function test_postgresql_rejects_cross_manifest_answer(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $owner = $this->user(AccountRole::Osds);
        $definition = FormDefinition::create(['module_id' => $manifest->module_id, 'key' => 'other_'.Str::random(5), 'name' => 'Other form', 'owner_id' => $owner->id]);
        $otherForm = FormVersion::create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $section = FormSection::create(['form_version_id' => $otherForm->id, 'key' => 'other', 'title' => 'Other', 'sort_order' => 1]);
        $otherQuestion = FormQuestion::create(['form_version_id' => $otherForm->id, 'form_section_id' => $section->id, 'key' => 'other', 'type' => 'short_text', 'label' => 'Other', 'is_required' => false, 'sort_order' => 1, 'validation_rules' => []]);
        $this->assertNotSame($manifest->form_version_id, $otherForm->id);
        $this->expectException(QueryException::class);
        DB::table('application_draft_answers')->insert(['application_draft_id' => $draft->id, 'form_question_id' => $otherQuestion->id, 'value' => json_encode('forged'), 'updated_by' => $school->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_evidence_parser_encryption_and_redaction_do_not_expose_locator(): void
    {
        $raw = 'https://docs.google.com/document/d/AbCdEfGhIjKlMnOp/edit';
        $parsed = app(GoogleEvidenceLocatorParser::class)->parse($raw);
        $this->assertSame('google_docs', $parsed->providerCode);
        $shared = app(GoogleEvidenceLocatorParser::class)->parse('https://docs.google.com/document/d/17c8XvMpbdXmbfPYMg-H2JBe1vnh1UGQV67yMdT4RHeA/edit?usp=drive_link');
        $this->assertSame('google_docs', $shared->providerCode);
        $this->assertSame('17c8XvMpbdXmbfPYMg-H2JBe1vnh1UGQV67yMdT4RHeA', $shared->fileId);
        $encrypted = app(EvidenceLocatorCipher::class)->encrypt($parsed);
        $this->assertStringNotContainsString('AbCdEfGhIjKlMnOp', $encrypted['locator_ciphertext']);
        $this->assertSame($parsed->fileId, app(EvidenceLocatorCipher::class)->decrypt($encrypted['locator_ciphertext'], $encrypted['encryption_key_version'])->fileId);
        config(['evidence.keys.v1' => 'base64:'.base64_encode(str_repeat('a', 32)), 'evidence.keys.v2' => 'base64:'.base64_encode(str_repeat('b', 32)), 'evidence.current_key_version' => 'v1']);
        $old = app(EvidenceLocatorCipher::class)->encrypt($parsed);
        config(['evidence.current_key_version' => 'v2']);
        $this->assertSame($parsed->fileId, app(EvidenceLocatorCipher::class)->decrypt($old['locator_ciphertext'], 'v1')->fileId);
        $this->assertSame('v2', app(EvidenceLocatorCipher::class)->encrypt($parsed)['encryption_key_version']);
        foreach (['http://drive.google.com/file/d/AbCdEfGhIjKlMnOp', 'https://drive.google.com/drive/folders/AbCdEfGhIjKlMnOp', 'https://docs.google.com/forms/d/AbCdEfGhIjKlMnOp', 'https://docs.google.com/document/d/AbCdEfGhIjKlMnOp/edit#secret', 'https://docs.google.com/document/d/AbCdEfGhIjKlMnOp/edit?redirect=https://example.test'] as $bad) {
            try {
                app(GoogleEvidenceLocatorParser::class)->parse($bad);
                $this->fail('Unsafe locator accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $actor = $this->user(AccountRole::Admin);
        app(AuditRecorder::class)->record('redaction.test', 'success', $actor, metadata: ['nested' => ['raw_locator' => $raw, 'google_file_id' => 'AbCdEfGhIjKlMnOp'], 'safe' => 'yes']);
        $json = json_encode(DB::table('audit_events')->latest('id')->first());
        $this->assertStringNotContainsString('AbCdEfGhIjKlMnOp', $json);
        $this->assertStringContainsString('yes', $json);
    }

    public function test_evidence_mutations_are_idempotent_concurrency_safe_and_review_copy_is_immutable(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $requirement = $manifest->requirementSetVersion->requirements()->first();
        $service = app(EvidenceReferenceService::class);
        $key = (string) Str::uuid();
        $first = $service->add($school, $draft, $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 1, $key);
        $this->assertSame(2, $first['lock_version']);
        $this->assertSame($first, $service->add($school, $draft, $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 1, $key));
        try {
            $service->remove($school, $draft, EvidenceReference::find($first['reference_id']), 1, (string) Str::uuid());
            $this->fail('Stale evidence write succeeded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('conflict', $e->errors());
        }
        $copyKey = (string) Str::uuid();
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 2, $copyKey);
        $this->assertSame($case->id, app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 2, $copyKey)->id);
        $reference = EvidenceReference::find($first['reference_id']);
        $other = $this->user(AccountRole::School, true);
        $response = $this->actingAs($other)->get(route('applicant.evidence.open', $reference));
        $response->assertForbidden();
        $this->assertStringNotContainsString('AbCdEfGhIjKlMnOp', $response->getContent());
        $this->assertStringNotContainsString('AbCdEfGhIjKlMnOp', $reference->latestRevision->toJson());
        $revisionId = $case->requirements()->first()->items()->value('evidence_reference_revision_id');
        $service->replace($school, $draft->fresh(), EvidenceReference::find($first['reference_id']), 'https://drive.google.com/file/d/ZyXwVuTsRqPoNmLk/view', 2, (string) Str::uuid());
        $this->assertSame($revisionId, $case->requirements()->first()->items()->value('evidence_reference_revision_id'));
        $this->expectException(QueryException::class);
        DB::table('synthetic_evidence_review_cases')->where('id', $case->id)->update(['draft_lock_version' => 999]);
    }

    public function test_evidence_finding_and_audit_are_atomic_when_audit_recording_fails(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $requirement = $manifest->requirementSetVersion->requirements()->firstOrFail();
        app(EvidenceReferenceService::class)->add($school, $draft, $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 1, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 2, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);
        $admin = $this->user(AccountRole::Admin);
        SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Isolated atomicity regression']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $caseRequirement = $case->requirements()->firstOrFail();
        $findingsBefore = SyntheticEvidenceReviewFinding::count();
        $auditsBefore = AuditEvent::count();
        // Inject only the downstream failure. Authorization, Livewire transport,
        // the domain service and actual PostgreSQL finding insert remain real.
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('Isolated audit sink unavailable'));
        Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class)
            ->set('findingType.'.$caseRequirement->id, 'completeness')
            ->set('outcome.'.$caseRequirement->id, 'meets_test_check')
            ->set('comment.'.$caseRequirement->id, 'Synthetic atomicity finding')
            ->call('saveFinding', $caseRequirement->id)
            ->assertHasErrors(['staffAction']);
        $this->assertSame($auditsBefore, AuditEvent::count());
        $this->assertSame($findingsBefore, SyntheticEvidenceReviewFinding::count(), 'A failed audit must roll back its associated append-only finding.');
    }

    public function test_reviewer_assignment_revocation_and_audit_are_atomic_when_audit_fails(): void
    {
        [, $institution, $manifest] = $this->scenario();
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        $this->actingAs($admin);
        DB::table('user_region_assignments')->insert(['user_id' => $admin->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subMinute()->toIso8601String(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertTrue(app(ScopeAuthorizer::class)->canAccessRegion($admin, $institution->primary_region_id));
        $assignment = SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subMinute(), 'reason' => 'Isolated assignment audit regression']);
        $page = Livewire::actingAs($admin)->test(ListSyntheticEvidenceReviewerAssignments::class);
        $assignments = SyntheticEvidenceReviewerAssignment::count();
        $audits = AuditEvent::count();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('Isolated grant audit failure'));
        $page->callTableAction('revoke', $assignment, data: ['revocation_reason' => 'Isolated revocation audit regression'])
            ->assertNotified('Action could not be confirmed');
        $this->assertSame($audits, AuditEvent::count());
        $this->assertSame($assignments, SyntheticEvidenceReviewerAssignment::count());
        $this->assertNull($assignment->fresh()->revoked_at, 'A failed mandatory revocation audit must not leave an unaudited authority mutation.');
    }

    private function atomicFindingFixture(): array
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $requirement = $manifest->requirementSetVersion->requirements()->firstOrFail();
        $reference = app(EvidenceReferenceService::class)->add($school, $draft, $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 1, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 2, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);
        $admin = $this->user(AccountRole::Admin);
        $assignment = SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Isolated atomicity matrix']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);

        return [$reviewer, $case, $case->requirements()->firstOrFail(), $assignment];
    }

    public function test_finding_atomic_transaction_rolls_back_audit_partial_work_and_preserves_history(): void
    {
        [$reviewer, $case, $requirement] = $this->atomicFindingFixture();
        $original = app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, null, 'completeness', 'meets_test_check', 'Earlier synthetic finding');
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count(), $original->fresh()->getRawOriginal()];
        $outerLevel = DB::transactionLevel();
        foreach (['before', 'after'] as $when) {
            $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use ($when, $outerLevel) {
                $this->assertSame($outerLevel + 1, DB::transactionLevel());
                $this->assertSame((new SyntheticEvidenceReviewFinding)->getConnection()->getPdo(), (new AuditEvent)->getConnection()->getPdo());
                if ($when === 'after') {
                    (new AuditRecorder)->record(...$arguments);
                }
                throw new \RuntimeException('Synthetic audit failure');
            });
            try {
                app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, null, 'completeness', 'needs_attention', 'Attempted synthetic finding');
                $this->fail('Audit failure must escape the service.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Synthetic audit failure', $e->getMessage());
            }
            $this->assertSame($outerLevel, DB::transactionLevel());
            $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count(), $original->fresh()->getRawOriginal()]);
        }
    }

    public function test_successful_finding_has_one_exact_audit_and_repeated_calls_remain_append_only(): void
    {
        [$reviewer, $case, $requirement] = $this->atomicFindingFixture();
        $revision = $requirement->items()->firstOrFail()->revision;
        $beforeFindings = SyntheticEvidenceReviewFinding::count();
        $beforeAudits = AuditEvent::count();
        foreach ([1, 2] as $iteration) {
            $finding = app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, $revision, 'document_match', 'meets_test_check', 'Synthetic paired finding');
            $audit = AuditEvent::where('action', 'evidence.test_finding_created')->where('resource_type', $finding->getMorphClass())->where('resource_id', (string) $finding->id)->sole();
            $this->assertSame($reviewer->id, $audit->actor_id);
            $this->assertSame($case->region_id, $audit->region_id);
            $this->assertSame($case->institution_id, $audit->institution_id);
            $this->assertSame($case->id, $audit->metadata['case_id']);
            $this->assertSame($requirement->documentary_requirement_id, $audit->metadata['requirement_id']);
            $this->assertSame($revision->id, $finding->evidence_reference_revision_id);
            $this->assertSame($beforeFindings + $iteration, SyntheticEvidenceReviewFinding::count());
            $this->assertSame($beforeAudits + $iteration, AuditEvent::count());
        }
        // Existing semantics deliberately append; this is not an idempotency claim.
        $this->assertSame(2, SyntheticEvidenceReviewFinding::where('case_requirement_id', $requirement->id)->count());
    }

    public function test_finding_database_and_validation_failures_leave_no_partial_records(): void
    {
        [$reviewer, $case, $requirement] = $this->atomicFindingFixture();
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count(), DB::transactionLevel()];
        try {
            app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, null, 'invalid', 'invalid');
            $this->fail('Invalid finding must fail.');
        } catch (ValidationException) {
        }
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count(), DB::transactionLevel()]);
        $missing = new SyntheticEvidenceReviewFinding(['id' => 2147483647]);
        try {
            app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, null, 'completeness', 'needs_attention', supersedes: $missing);
            $this->fail('Missing superseded finding must fail the PostgreSQL foreign key.');
        } catch (QueryException) {
        }
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count(), DB::transactionLevel()]);
    }

    public function test_finding_write_reloads_current_actor_and_requirement_authority(): void
    {
        [$reviewer, $case, $requirement, $assignment] = $this->atomicFindingFixture();
        $reviewer->getAllPermissions(); // Deliberately cache earlier authority.
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
        $remaining = $reviewer->getAllPermissions()->pluck('name')->reject(fn ($name) => $name === 'evidence.review.findings.create')->all();
        $changed = $reviewer->fresh();
        $changed->syncRoles([]);
        $changed->syncPermissions($remaining);
        try {
            app(SyntheticEvidenceReviewService::class)->finding($reviewer, $requirement, null, 'completeness', 'meets_test_check');
            $this->fail('Stale actor permission must not authorize a finding.');
        } catch (AuthorizationException) {
        }
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        $changed->givePermissionTo('evidence.review.findings.create');
        foreach (['expired', 'future', 'revoked'] as $state) {
            $changed = $this->user(AccountRole::Chedro, true);
            DB::table('user_region_assignments')->insert(['user_id' => $changed->id, 'region_id' => $case->region_id, 'starts_at' => now()->subDay()->toIso8601String(), 'created_at' => now(), 'updated_at' => now()]);
            $grant = SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $changed->id, 'region_id' => $case->region_id, 'module_id' => $case->module_id, 'starts_at' => ($state === 'future' ? now()->addHour() : now()->subDay())->toIso8601String(), 'ends_at' => $state === 'expired' ? now()->subMinute()->toIso8601String() : null, 'granted_by' => $assignment->granted_by, 'reason' => 'Explicit-offset ineffective assignment']);
            if ($state === 'revoked') {
                DB::table('synthetic_evidence_reviewer_assignments')->where('id', $grant->id)->update(['revoked_at' => now()->toIso8601String(), 'revoked_by' => $assignment->granted_by]);
            }
            $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
            try {
                app(SyntheticEvidenceReviewService::class)->finding($changed, $requirement, null, 'completeness', 'meets_test_check');
                $this->fail('Ineffective assignment must deny.');
            } catch (AuthorizationException) {
            }
            $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        }
    }

    public function test_finding_rejects_unreviewed_revision_wrong_pairs_and_missing_requirement_without_mutation(): void
    {
        [$reviewer, $case, $requirement, $assignment] = $this->atomicFindingFixture();
        $originalItem = $requirement->items()->firstOrFail();
        $draft = ApplicationDraft::findOrFail($case->application_draft_id);
        $owner = User::findOrFail($case->applicant_id);
        $reference = EvidenceReference::findOrFail($originalItem->revision->evidence_reference_id);
        $replaced = app(EvidenceReferenceService::class)->replace($owner, $draft, $reference, 'https://drive.google.com/file/d/ZyXwVuTsRqPoNmLk/view', $draft->lock_version, (string) Str::uuid());
        $revision = $reference->fresh()->latestRevision;
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
        Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class)
            ->call('saveFinding', $requirement->id, $revision->public_id)->assertForbidden();
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        $this->assertSame($originalItem->evidence_reference_revision_id, $originalItem->fresh()->evidence_reference_revision_id);
        Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class)
            ->call('saveFinding', 2147483647)->assertNotFound();
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        $this->actingAs(User::findOrFail($assignment->granted_by));
        foreach (['region', 'module'] as $wrong) {
            $actor = $this->user(AccountRole::Chedro, true);
            $region = $wrong === 'region' ? Region::create(['code' => 'G-OTHER', 'name' => 'Other synthetic region'])->id : $case->region_id;
            $module = $wrong === 'module' ? OsdsModule::where('id', '!=', $case->module_id)->firstOrFail()->id : $case->module_id;
            DB::table('user_region_assignments')->insert(['user_id' => $actor->id, 'region_id' => $region, 'starts_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()]);
            SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $actor->id, 'region_id' => $region, 'module_id' => $module, 'starts_at' => now()->subDay(), 'granted_by' => $assignment->granted_by, 'reason' => 'Wrong exact pair regression']);
            $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
            try {
                app(SyntheticEvidenceReviewService::class)->finding($actor, $requirement, null, 'completeness', 'meets_test_check');
                $this->fail('Wrong exact pair must be denied.');
            } catch (AuthorizationException) {
            }
            $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        }
    }

    public function test_atomic_finding_failure_has_safe_livewire_feedback_and_no_serialized_audit_or_locator(): void
    {
        [$reviewer, $case, $requirement] = $this->atomicFindingFixture();
        Log::spy();
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('PRIVATE_ATOMIC_AUDIT_FAILURE'));
        $page = Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class)
            ->set('findingType.'.$requirement->id, 'completeness')->set('outcome.'.$requirement->id, 'needs_attention')
            ->set('comment.'.$requirement->id, 'Retain local synthetic remarks')->call('saveFinding', $requirement->id)
            ->assertHasErrors(['staffAction'])->assertSet('comment.'.$requirement->id, 'Retain local synthetic remarks')->assertSee('Support reference:');
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['PRIVATE_ATOMIC_AUDIT_FAILURE', 'AbCdEfGhIjKlMnOp', 'https://drive.google.com/file', 'SQLSTATE'] as $private) {
            $this->assertStringNotContainsString($private, $response);
        }
        $this->assertSame($before, [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action result unconfirmed' && array_keys($context) === ['reference', 'exception_class'] && $context['exception_class'] === \RuntimeException::class);
        $this->assertSame([null], $page->effects['returns']);
    }

    public function test_finding_confirmation_requires_delivered_atomic_success_and_cannot_be_forged(): void
    {
        [$reviewer, $case, $requirement] = $this->atomicFindingFixture();
        $before = [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()];
        $page = Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class)
            ->set('findingType.'.$requirement->id, 'completeness')->set('outcome.'.$requirement->id, 'meets_test_check')
            ->call('saveFinding', $requirement->id)->assertHasNoErrors()->assertSee('Test finding saved.');
        $this->assertSame([$before[0] + 1, $before[1] + 1], [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new \RuntimeException('Synthetic later audit failure'));
        $page->call('saveFinding', $requirement->id)->assertHasErrors(['staffAction'])->assertSet('findingConfirmation', null);
        $this->assertSame([$before[0] + 1, $before[1] + 1], [SyntheticEvidenceReviewFinding::count(), AuditEvent::count()]);
        try {
            $page->set('findingConfirmation', 'Fabricated success');
            $this->fail('Confirmation must be server controlled.');
        } catch (CannotUpdateLockedPropertyException) {
            $this->assertTrue(true);
        }
    }

    public function test_chedro_requires_permission_assignment_and_region_scope_for_findings(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $requirement = $manifest->requirementSetVersion->requirements()->first();
        app(EvidenceReferenceService::class)->add($school, $draft, $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 1, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 2, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);
        $service = app(SyntheticEvidenceReviewService::class);
        try {
            $service->authorizeReviewer($reviewer, $case);
            $this->fail('Unassigned reviewer authorized.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
        SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $this->user(AccountRole::Admin)->id, 'reason' => 'Synthetic review']);
        try {
            $service->authorizeReviewer($reviewer, $case);
            $this->fail('Reviewer without regional scope was authorized.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $this->user(AccountRole::Admin)->id, 'created_at' => now(), 'updated_at' => now()]);
        $service->authorizeReviewer($reviewer, $case);
        $finding = $service->finding($reviewer, $case->requirements()->first(), null, 'completeness', 'meets_test_check', 'Synthetic check');
        $this->assertInstanceOf(SyntheticEvidenceReviewFinding::class, $finding);
    }

    public function test_application_submission_is_idempotent_immutable_and_consumes_the_working_revision(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Synthetic Association'], 1, (string) Str::uuid());
        $requirement = $manifest->requirementSetVersion->requirements()->first();
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        $key = (string) Str::uuid();
        $submission = app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, $key);

        $this->assertSame($submission->id, app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, $key)->id);
        $this->assertSame('submitted', $application->fresh()->state);
        $this->assertSame('consumed', $submission->workingRevision->state);
        $this->assertDatabaseCount('application_submissions', 1);
        $this->assertDatabaseHas('application_submission_answers', ['submission_id' => $submission->id, 'is_applicable' => true]);
        $this->assertDatabaseHas('application_submission_evidence_items', ['evidence_reference_revision_id' => $draft->evidenceReferences()->first()->latestRevision->id]);

        try {
            app(ApplicantDraftService::class)->save($school, $draft->fresh(), ['name' => 'Changed'], 3, (string) Str::uuid());
            $this->fail('A consumed draft was edited.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->expectException(QueryException::class);
        DB::table('application_submission_answers')->where('submission_id', $submission->id)->update(['question_key' => 'forged']);
    }

    public function test_assigned_chedro_can_screen_request_corrections_and_applicant_can_resubmit(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Original'], 1, (string) Str::uuid());
        $requirement = $manifest->requirementSetVersion->requirements()->first();
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        $first = app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);
        $admin = $this->user(AccountRole::Admin, true);
        $this->actingAs($admin);
        SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Synthetic intake']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $sourceRevision = $first->requirements()->first()->items()->first()->revision;
        $this->actingAs($reviewer)
            ->get(route('application.evidence.open', [$application, $sourceRevision]))
            ->assertRedirect('https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view');
        $lifecycle = app(ApplicationLifecycleService::class);
        $lifecycle->beginScreening($reviewer, $application->fresh());
        $request = $lifecycle->requestCorrection($reviewer, $application->fresh(), 'Please clarify the organization name.', [[
            'target_kind' => 'question', 'form_question_id' => (string) $first->answers()->first()->form_question_id,
            'issue_category' => 'clarification', 'instruction' => 'Use the registered organization name.',
        ]]);
        $this->assertIsInt($request->items()->first()->form_question_id);
        $application = $application->fresh();
        $correctionDraft = $application->currentWorkingRevision->draft;
        $clonedRevision = $correctionDraft->evidenceReferences()->first()->latestRevision;
        $this->assertSame($sourceRevision->id, $clonedRevision->source_evidence_revision_id);
        $this->assertSame($sourceRevision->locator_ciphertext, $clonedRevision->locator_ciphertext);
        $this->assertSame('correction_requested', $application->state);

        $this->actingAs($school)->get(route('applicant.applications.show', $application))->assertOk()->assertSee('Use the registered organization name.');
        DB::table('institution_representatives')->where('user_id', $school->id)->update(['revoked_at' => now(), 'revoked_by' => $admin->id]);
        $this->get(route('applicant.applications.show', $application))->assertNotFound()->assertDontSee('Use the registered organization name.');
        DB::table('institution_representatives')->where('user_id', $school->id)->update(['revoked_at' => null, 'revoked_by' => null]);

        app(ApplicantDraftService::class)->save($school, $correctionDraft, ['name' => 'Corrected'], 1, (string) Str::uuid());
        $second = $lifecycle->submit($school, $application, 4, 2, (string) Str::uuid());
        $this->assertSame(2, $second->submission_sequence);
        $this->assertSame($first->id, $second->prior_submission_id);
        $this->assertSame($request->id, $second->answered_correction_request_id);
        $this->assertSame('resubmitted', $application->fresh()->state);
        $this->assertDatabaseCount('application_submissions', 2);
        $trackingUrl = route('applicant.applications.show', $application);
        $this->actingAs($school)->get($trackingUrl)->assertOk()->assertSee('Status history');
        $historyBefore = DB::table('application_state_history')->where('application_id', $application->id)->get()->toJson();
        $submissionsBefore = DB::table('application_submissions')->where('application_id', $application->id)->get()->toJson();
        $auditCount = DB::table('audit_events')->count();
        DB::table('institution_representatives')->where('user_id', $school->id)->update(['revoked_at' => now(), 'revoked_by' => $admin->id]);
        $this->get($trackingUrl)->assertNotFound()->assertDontSee('Use the registered organization name.')->assertDontSee($institution->name);
        $this->getJson($trackingUrl)->assertNotFound()->assertDontSee('Use the registered organization name.');
        $this->assertSame($historyBefore, DB::table('application_state_history')->where('application_id', $application->id)->get()->toJson());
        $this->assertSame($submissionsBefore, DB::table('application_submissions')->where('application_id', $application->id)->get()->toJson());
        $this->assertSame($auditCount, DB::table('audit_events')->count());
        $this->assertSame($school->id, $application->fresh()->applicant_id);
    }

    public function test_tracking_rechecks_current_representation_and_sandbox_authority(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $url = route('applicant.applications.show', $draft->application);
        $grant = DB::table('institution_representatives')->where('user_id', $school->id)->first();
        $this->actingAs($school)->get($url)->assertOk()->assertSee($institution->name);
        foreach ([
            ['revoked_at' => now()],
            ['ends_at' => now()->subSecond()],
            ['starts_at' => now()->addDay()],
            ['verified_at' => null],
        ] as $change) {
            DB::table('institution_representatives')->where('id', $grant->id)->update($change);
            $this->get($url)->assertNotFound()->assertDontSee($institution->name)->assertDontSee('Status history');
            $this->getJson($url)->assertNotFound()->assertDontSee($institution->name);
            DB::table('institution_representatives')->where('id', $grant->id)->update(['revoked_at' => null, 'ends_at' => null, 'starts_at' => $grant->starts_at, 'verified_at' => $grant->verified_at]);
        }
        $otherSchool = $this->user(AccountRole::School, true);
        $this->actingAs($otherSchool)->get($url)->assertNotFound();
        $this->actingAs($school);
        DB::table('institution_representatives')->where('id', $grant->id)->update(['user_id' => $otherSchool->id]);
        $this->get($url)->assertNotFound()->assertDontSee($institution->name);
        DB::table('institution_representatives')->where('id', $grant->id)->update(['user_id' => $school->id]);
        $otherInstitution = Institution::withoutEvents(fn () => Institution::forceCreate(['public_id' => (string) Str::uuid(), 'name' => 'Other synthetic organization', 'is_synthetic' => true, 'primary_region_id' => $institution->primary_region_id]));
        DB::table('institution_representatives')->where('id', $grant->id)->update(['institution_id' => $otherInstitution->id]);
        $this->get($url)->assertNotFound()->assertDontSee($institution->name);
        DB::table('institution_representatives')->where('id', $grant->id)->update(['institution_id' => $institution->id]);
        $school->removeRole(AccountRole::School->value);
        $school->givePermissionTo('applicant.portal.access');
        $this->get($url)->assertNotFound();
        $school->assignRole(AccountRole::School->value);
        $school->is_synthetic = false;
        $this->get($url)->assertForbidden()->assertDontSee($institution->name);
        $school->is_synthetic = true;
        $this->app->instance('env', 'production');
        try {
            $this->get($url)->assertNotFound();
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->get($url)->assertOk();
        $school->status = 'suspended';
        $this->get($url)->assertForbidden()->assertDontSee($institution->name);
    }

    public function test_unassigned_or_out_of_region_reviewer_cannot_access_application_intake(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Synthetic Association'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);

        $this->actingAs($reviewer)->get('/admin/synthetic-application-intake?application='.$application->public_id)->assertNotFound();
    }

    public function test_hidden_answers_are_snapshotted_for_reproducibility_but_not_shown_in_ordinary_intake(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Hidden', 'private_notes' => 'SENSITIVE-HIDDEN-VALUE'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        $submission = app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $this->assertDatabaseHas('application_submission_answers', ['submission_id' => $submission->id, 'question_key' => 'private_notes', 'is_applicable' => false]);

        $reviewer = $this->user(AccountRole::Chedro, true);
        $admin = $this->user(AccountRole::Admin, true);
        $this->actingAs($admin);
        SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Synthetic intake']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($reviewer)->get('/admin/synthetic-application-intake?application='.$application->public_id)
            ->assertOk()
            ->assertSee('Questionnaire answers')
            ->assertSee('Required documents')
            ->assertDontSee('SENSITIVE-HIDDEN-VALUE');
    }

    public function test_retired_manifest_preserves_submitted_history_and_blocks_new_corrections(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Synthetic Association'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $reviewer = $this->user(AccountRole::Chedro, true);
        $admin = $this->user(AccountRole::Admin, true);
        $this->actingAs($admin);
        SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Synthetic intake']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $lifecycle = app(ApplicationLifecycleService::class);
        $lifecycle->beginScreening($reviewer, $application->fresh());
        PublicationManifestRetirement::create(['publication_manifest_id' => $manifest->id, 'retired_by' => $admin->id, 'reason' => 'Synthetic retirement', 'correlation_id' => (string) Str::uuid(), 'retired_at' => now()]);

        try {
            $lifecycle->requestCorrection($reviewer, $application->fresh(), 'Blocked', [['target_kind' => 'general', 'issue_category' => 'clarification', 'instruction' => 'Blocked']]);
            $this->fail('A correction was created after manifest retirement.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->assertDatabaseCount('application_correction_requests', 0);
        $this->actingAs($school)->get(route('applicant.applications.show', $application))->assertOk()->assertSee('editing and resubmission are unavailable');
    }

    public function test_both_reviewer_queues_fail_closed_for_ineffective_authority(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Private queue fixture'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin, true);
        $otherRegion = Region::create(['code' => 'OTHER', 'name' => 'Other region']);
        $otherModule = OsdsModule::where('id', '!=', $manifest->module_id)->firstOrFail();

        foreach (['application', 'evidence'] as $kind) {
            foreach (['none', 'expired', 'revoked', 'future', 'wrong_region', 'wrong_module', 'no_scope', 'no_permission', 'nonsynthetic', 'inactive', 'wrong_workflow', 'production', 'valid'] as $condition) {
                $reviewer = $this->user(AccountRole::Chedro, true);
                $this->actingAs($admin);
                $permission = $kind === 'application' ? 'applications.intake.view' : 'evidence.reviews.view';
                $assignmentClass = $kind === 'application' ? SyntheticApplicationReviewerAssignment::class : SyntheticEvidenceReviewerAssignment::class;
                if ($condition === 'wrong_workflow') {
                    $assignmentClass = $kind === 'application' ? SyntheticEvidenceReviewerAssignment::class : SyntheticApplicationReviewerAssignment::class;
                }
                if ($condition !== 'none') {
                    $assignmentClass::create([
                        'reviewer_id' => $reviewer->id,
                        'region_id' => $condition === 'wrong_region' ? $otherRegion->id : $institution->primary_region_id,
                        'module_id' => $condition === 'wrong_module' ? $otherModule->id : $manifest->module_id,
                        'starts_at' => $condition === 'future' ? now()->addDay() : now()->subDays(2),
                        'ends_at' => $condition === 'expired' ? now()->subDay() : null,
                        'revoked_at' => $condition === 'revoked' ? now()->subHour() : null,
                        'revoked_by' => $condition === 'revoked' ? $admin->id : null,
                        'revocation_reason' => $condition === 'revoked' ? 'Synthetic revocation' : null,
                        'granted_by' => $admin->id, 'reason' => 'Isolated queue regression',
                    ]);
                }
                if ($condition !== 'no_scope') {
                    DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $condition === 'wrong_region' ? $otherRegion->id : $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
                }
                if ($condition === 'no_permission') {
                    $reviewer->removeRole(AccountRole::Chedro->value);
                }
                if ($condition === 'nonsynthetic') {
                    $reviewer->is_synthetic = false;
                }
                if ($condition === 'inactive') {
                    $reviewer->status = 'suspended';
                }
                $this->actingAs($reviewer);
                if ($condition === 'production') {
                    $this->app->instance('env', 'production');
                }
                try {
                    $scope = app(ReviewerQueueScope::class);
                    $query = $kind === 'application'
                        ? $scope->applications(Application::query()->whereNotNull('current_submission_id'), $reviewer)
                        : $scope->evidenceReviews(SyntheticEvidenceReviewCase::query(), $reviewer);
                    $expected = $condition === 'valid' ? 1 : 0;
                    $this->assertSame($expected, (clone $query)->count(), "$kind / $condition count");
                    $this->assertSame($expected, (clone $query)->where('institution_id', $institution->id)->paginate(1)->total(), "$kind / $condition filtered pagination");
                    $this->assertCount($expected, (clone $query)->where('public_id', 'like', '%')->get(), "$kind / $condition search");
                    $page = $kind === 'application' ? new SyntheticApplicationIntake : new SyntheticEvidenceReviews;
                    $this->assertCount($expected, $kind === 'application' ? $page->applications() : $page->cases());
                    if (in_array($condition, ['none', 'valid'], true)) {
                        $queuePath = $kind === 'application' ? '/admin/synthetic-application-intake' : '/admin/synthetic-evidence-reviews';
                        $response = $this->get($queuePath.'?search=Synthetic&page=1&service='.$manifest->module->code)->assertOk();
                        if ($condition === 'none') {
                            $response->assertDontSee($institution->name)->assertDontSee($manifest->module->name);
                        } else {
                            $response->assertSee($institution->name);
                        }
                    }
                    if (in_array($condition, ['none', 'expired', 'revoked', 'future', 'wrong_region', 'wrong_module', 'no_scope', 'wrong_workflow'], true)) {
                        if ($kind === 'application') {
                            $this->get('/admin/synthetic-application-intake?application='.$application->public_id)->assertNotFound();
                        } else {
                            $this->get('/admin/synthetic-evidence-reviews?case='.$case->public_id)->assertForbidden();
                        }
                    }
                } finally {
                    $this->app->instance('env', 'testing');
                }
            }
        }
    }

    public function test_reviewer_queue_scope_keeps_region_and_module_pairs_together(): void
    {
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        $this->actingAs($admin);
        $regions = collect(['PAIR-A', 'PAIR-B'])->map(fn ($code) => Region::create(['code' => $code, 'name' => $code]));
        $modules = OsdsModule::whereIn('code', ['registration', 'acsa'])->orderBy('id')->get();
        foreach ($regions as $index => $region) {
            DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $region->id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignmentClass) {
                $assignmentClass::create(['reviewer_id' => $reviewer->id, 'region_id' => $region->id, 'module_id' => $modules[$index]->id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Exact pair regression']);
            }
        }
        // A PostgreSQL-derived four-row grid tests all pair combinations without fabricating application records.
        $grid = DB::table('regions')->crossJoin((new OsdsModule)->getTable().' as paired_modules')->whereIn('regions.id', $regions->pluck('id'))->whereIn('paired_modules.id', $modules->pluck('id'))
            ->selectRaw('regions.id as region_id, regions.id as routing_region_id, paired_modules.id as module_id');
        $scope = app(ReviewerQueueScope::class);
        foreach (['applications', 'evidenceReviews'] as $method) {
            $query = $scope->$method(Application::query()->fromSub(clone $grid, 'authorized_grid'), $reviewer);
            $this->assertSame(2, (clone $query)->count());
            $this->assertSame(0, (clone $query)->where('region_id', $regions[0]->id)->where('module_id', $modules[1]->id)->count());
            $this->assertSame(0, (clone $query)->where('region_id', $regions[1]->id)->where('module_id', $modules[0]->id)->paginate(1)->total());
        }
    }

    public function test_draft_editor_reauthorizes_livewire_reads_and_mutations_after_representation_ends(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'SEC003-PROTECTED-ANSWER'], 1, (string) Str::uuid());
        $requirement = $manifest->requirementSetVersion->requirements()->firstOrFail();
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $requirement, 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $draft->refresh();
        $reference = $draft->evidenceReferences()->firstOrFail();
        $before = [];
        foreach (['application_drafts', 'application_draft_answers', 'evidence_references', 'evidence_reference_revisions', 'applications', 'application_submissions', 'audit_events'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        foreach (['revoked_at' => now(), 'ends_at' => now()->subSecond()] as $field => $value) {
            foreach ([['$refresh'], ['checkProgress'], ['goToStep', 1], ['evidenceFor', $requirement->id], ['isReadOnly'], ['save'], ['addEvidence', $requirement->id], ['removeEvidence', $reference->public_id], ['sendDocumentsForTestReview'], ['submitTestApplication']] as $action) {
                $component = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])
                    ->assertSet("answers.{$question->id}", 'SEC003-PROTECTED-ANSWER');
                DB::table('institution_representatives')->where('user_id', $school->id)->update([$field => $value]);
                $component->call(...$action)->assertNotFound()->assertDontSee('SEC003-PROTECTED-ANSWER', false, false)
                    ->assertDontSee($reference->public_id, false, false)->assertDontSee($institution->name, false, false);
                DB::table('institution_representatives')->where('user_id', $school->id)->update([$field => null]);
            }
        }
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), "$table changed after denied Livewire requests");
        }
        Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])
            ->call('goToStep', 1)->assertOk()->call('checkProgress')->assertOk();
    }

    public function test_draft_editor_mount_and_hydration_require_current_identity_permissions_and_scope(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $grant = DB::table('institution_representatives')->where('user_id', $school->id)->first();
        $other = $this->user(AccountRole::School, true);
        $otherInstitution = Institution::withoutEvents(fn () => Institution::forceCreate(['public_id' => (string) Str::uuid(), 'name' => 'Other synthetic organization', 'is_synthetic' => true, 'primary_region_id' => $institution->primary_region_id]));
        foreach ([
            ['revoked_at' => now()], ['ends_at' => now()->subSecond()], ['starts_at' => now()->addDay()],
            ['verified_at' => null], ['user_id' => $other->id], ['institution_id' => $otherInstitution->id],
        ] as $change) {
            $component = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])->assertOk();
            DB::table('institution_representatives')->where('id', $grant->id)->update($change);
            $component->call('$refresh')->assertNotFound();
            $this->get(route('applicant.drafts.edit', $draft))->assertNotFound();
            DB::table('institution_representatives')->where('id', $grant->id)->update((array) $grant);
        }
        foreach (['permission', 'portal_permission', 'inactive', 'nonsynthetic', 'eligibility', 'production'] as $condition) {
            $component = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])->assertOk();
            if ($condition === 'permission' || $condition === 'portal_permission') {
                $school->removeRole(AccountRole::School->value);
                $school->syncPermissions($condition === 'permission' ? ['applicant.portal.access'] : ['applications.draft.view']);
            } elseif ($condition === 'inactive') {
                DB::table('users')->where('id', $school->id)->update(['status' => 'suspended']);
            } elseif ($condition === 'nonsynthetic') {
                DB::table('users')->where('id', $school->id)->update(['is_synthetic' => false]);
            } elseif ($condition === 'eligibility') {
                DB::table('applicant_sandbox_eligibility_grants')->where('institution_id', $institution->id)->update(['ends_at' => now()->subSecond()]);
            } else {
                $this->app->instance('env', 'production');
            }
            try {
                $component->call('checkProgress')->assertNotFound();
                Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])->assertNotFound();
            } finally {
                $this->app->instance('env', 'testing');
                DB::table('users')->where('id', $school->id)->update(['status' => 'active', 'is_synthetic' => true]);
                DB::table('applicant_sandbox_eligibility_grants')->where('institution_id', $institution->id)->update(['ends_at' => null]);
                $school->syncPermissions([]);
                $school->assignRole(AccountRole::School->value);
            }
        }
    }

    public function test_applicant_unsaved_answers_survive_steps_and_cannot_be_submitted_or_checked_as_saved(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $editor = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft])
            ->set('answers.'.$question->id, 'Show notes')
            ->call('goToStep', 1)->call('goToStep', 0)
            ->assertSet('answers.'.$question->id, 'Show notes')->assertSee('Private notes')
            ->call('checkProgress')->assertHasErrors('progress')
            ->call('submitTestApplication')->assertHasErrors('submission');
        $this->assertDatabaseCount('application_submissions', 0);
        $editor->call('save')->assertSet('saveState', 'Saved')->call('checkProgress')->assertSet('missing', []);
        Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])
            ->assertSet('answers.'.$question->id, 'Show notes');
    }

    public function test_applicant_replays_unconfirmed_answer_and_evidence_operations_with_original_keys(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $editor = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft])
            ->set('answers.'.$question->id, 'Confirmed once');
        $key = (string) Uuid::uuid5($editor->get('saveKey'), hash('sha256', json_encode([1, ['name' => 'Confirmed once']])));
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Confirmed once'], 1, $key);
        $editor->call('save')->assertSet('saveState', 'Saved')->assertSet('lockVersion', 2);
        $this->assertSame(2, $draft->fresh()->lock_version);
        $this->assertDatabaseCount('application_draft_idempotency_keys', 1);
        $requirement = $manifest->requirementSetVersion->requirements->first();
        $locator = 'https://drive.google.com/file/d/SyntheticReplayFile12345/view';
        $editor->set('evidenceInputs.'.$requirement->id, $locator);
        $evidenceKey = (string) Uuid::uuid5($editor->get('evidenceKey'), hash('sha256', json_encode(['add', (string) $requirement->id, 2, $locator])));
        app(EvidenceReferenceService::class)->add($school, $draft, $requirement, $locator, 2, $evidenceKey);
        $editor->call('addEvidence', $requirement->id)->assertSet('lockVersion', 3)->assertSet('evidenceInputs', []);
        $this->assertDatabaseCount('evidence_references', 1);
        $this->assertDatabaseCount('evidence_reference_revisions', 1);
        $this->assertSame(3, $draft->fresh()->lock_version);
    }

    public function test_applicant_save_failure_preserves_input_and_redacts_exception_details(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $this->mock(ApplicantDraftService::class, fn ($mock) => $mock->shouldReceive('save')->once()->andThrow(new \RuntimeException('PRIVATE-LOCATOR-SQL-BINDINGS')));
        Log::spy();
        Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft])
            ->set('answers.'.$question->id, 'Private unsaved answer')->call('save')
            ->assertSet('saveState', 'Save failed')->assertSet('answers.'.$question->id, 'Private unsaved answer')
            ->assertSee('We could not confirm this action')->assertDontSee('PRIVATE-LOCATOR-SQL-BINDINGS');
        $this->assertSame(1, $draft->fresh()->lock_version);
        $this->assertDatabaseCount('application_draft_answers', 0);
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $metadata) => ! str_contains(json_encode([$message, $metadata]), 'PRIVATE-LOCATOR') && ! str_contains(json_encode($metadata), 'Private unsaved answer'));
    }

    public function test_applicant_retired_draft_stays_readable_but_blocks_save_and_progress(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Preserved'], 1, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin);
        app(QuestionnairePublicationService::class)->retire($admin, $manifest, 'Synthetic browser retirement');
        Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])
            ->assertSee('Read-only application')->assertSet('answers.'.$question->id, 'Preserved')
            ->call('checkProgress')->assertHasErrors('progress')
            ->set('answers.'.$question->id, 'Unsaved replacement')->call('save')
            ->assertSet('saveState', 'Save failed')->assertSet('answers.'.$question->id, 'Unsaved replacement');
        $this->assertSame(2, $draft->fresh()->lock_version);
        $this->assertDatabaseHas('application_draft_answers', ['application_draft_id' => $draft->id, 'value' => json_encode('Preserved')]);
    }

    public function test_new_multiple_choice_controls_round_trip_array_answers_without_weakening_required_validation(): void
    {
        [$school, $institution, $manifest, $question] = $this->scenario(true);
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $multiple = $manifest->formVersion->sections->flatMap->questions->firstWhere('type', 'multiple_choice');
        $editor = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft])
            ->assertSet('answers.'.$multiple->id, [])
            ->set('answers.'.$question->id, 'Synthetic array answer')
            ->call('save')->assertSet('saveState', 'Saved')
            ->call('checkProgress')->assertSet('missing', ['Programs'])
            ->set('answers.'.$multiple->id, ['primary'])
            ->call('save')->assertSet('saveState', 'Saved')
            ->call('checkProgress')->assertSet('missing', []);
        $this->assertSame(['primary'], $draft->answers()->where('form_question_id', $multiple->id)->firstOrFail()->value);
        Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft->fresh()])->assertSet('answers.'.$multiple->id, ['primary']);
    }

    public function test_chedro_paginated_filters_options_and_livewire_reauthorization_keep_assignment_scope(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        $this->actingAs($admin);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $grants = [];
        foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignment) {
            $grants[] = $assignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Batch D scoped pagination']);
        }
        for ($index = 0; $index < 12; $index++) {
            $this->actingAs($reviewer);
            $org = Institution::create(['name' => 'Queue School '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'primary_region_id' => $institution->primary_region_id, 'organization_type' => 'hei', 'is_synthetic' => true]);
            InstitutionRepresentative::create(['institution_id' => $org->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
            ApplicantSandboxEligibilityGrant::create(['institution_id' => $org->id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Batch D fixture']);
            $draft = app(ApplicantDraftService::class)->start($school, $org, $manifest);
            app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Exact Mixed_CASE'], 1, (string) Str::uuid());
            app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
            $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
            $application = $draft->application;
            app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        }
        $this->actingAs($reviewer);
        foreach ([new SyntheticApplicationIntake, new SyntheticEvidenceReviews] as $page) {
            $records = fn () => $page instanceof SyntheticApplicationIntake ? $page->applications() : $page->cases();
            $this->assertSame(12, $records()->total());
            $this->assertCount(10, $records());
            $page->setPage(2);
            $this->assertCount(2, $records());
            $page->search = 'School 01';
            $page->updated('search');
            $page->service = $manifest->module->code;
            $this->assertSame(1, $records()->total());
            $page->search = '%';
            $this->assertSame(0, $records()->total(), 'Search treats SQL wildcard input literally');
            $page->search = '';
            $page->service = 'unassigned';
            $this->assertSame(0, $records()->total());
            $this->assertCount(1, $page->queueOptions()['services']);
        }
        $evidence = Livewire::actingAs($reviewer)->test(SyntheticEvidenceReviews::class, ['case' => $case->public_id]);
        $intake = Livewire::actingAs($reviewer)->test(SyntheticApplicationIntake::class, ['application' => $application->public_id]);
        foreach ($grants as $grant) {
            $grant->update(['revoked_at' => now(), 'revoked_by' => $admin->id, 'revocation_reason' => 'Batch D reauthorization']);
        }
        $intake->call('beginScreening')->assertNotFound();
        $evidence->call('saveFinding', $case->requirements()->first()->id)->assertForbidden();
        $this->assertDatabaseCount('synthetic_evidence_review_findings', 0);
        $this->assertSame('submitted', $application->fresh()->state);
        foreach ([new SyntheticApplicationIntake, new SyntheticEvidenceReviews] as $page) {
            $page->search = 'Queue';
            $records = $page instanceof SyntheticApplicationIntake ? $page->applications() : $page->cases();
            $this->assertSame(0, $records->total());
            $this->assertCount(0, $page->queueOptions()['services']);
            $this->assertCount(0, $page->queueOptions()['regions']);
        }
    }

    public function test_intake_livewire_helper_does_not_return_answers_without_answer_permission(): void
    {
        [$school, $institution, $manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'SYNTHETIC-ANSWER-DISCLOSURE-PROBE'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $reviewer->givePermissionTo(['platform.admin.access', 'applications.intake.view']);
        SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Isolated helper disclosure probe']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertFalse($reviewer->can('applications.intake.answers.view'));
        $page = Livewire::actingAs($reviewer)->withQueryParams(['application' => $application->public_id])->test(SyntheticApplicationIntake::class);
        $page->assertDontSee('SYNTHETIC-ANSWER-DISCLOSURE-PROBE');
        try {
            $page->call('selected');
            $this->fail('The presentation helper remained callable.');
        } catch (CannotCallComputedDirectlyException) {
            $this->assertTrue(true);
        }
        $returned = json_encode(data_get($page->effects, 'returns.0'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('SYNTHETIC-ANSWER-DISCLOSURE-PROBE', $returned, 'A directly invoked Livewire helper must not serialize answers without the answer-view permission.');
        $this->assertHelperHttpDenied($page, 'selected');
        $this->assertHelperHttpDenied($page, 'submittedCopy');
    }

    private function assertHelperHttpDenied($page, string $method): void
    {
        config(['app.debug' => false]);
        $response = $this->postJson(app('livewire')->getUpdateUri(), ['components' => [['snapshot' => json_encode($page->snapshot, JSON_THROW_ON_ERROR), 'updates' => (object) [], 'calls' => [['method' => $method, 'params' => []]]]]], ['X-Livewire' => 'true']);
        $response->assertStatus($method === 'queueQuery' ? 419 : 500)->assertDontSee('SYNTHETIC-ANSWER-DISCLOSURE-PROBE')->assertDontSee('current_submission')->assertDontSee('"answers"', false);
    }

    public function test_sec005_current_history_payload_permissions_revocation_and_evidence_separation(): void
    {
        [$school,$institution,$manifest,$question] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'SYNTHETIC-HISTORY-ANSWER', 'private_notes' => 'SYNTHETIC-HIDDEN-ANSWER'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        $application = $draft->application;
        $lifecycle = app(ApplicationLifecycleService::class);
        $old = $lifecycle->submit($school, $application, 1, 3, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        $grant = SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'SEC005 matrix']);
        SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Separate SEC005 evidence matrix']);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        $lifecycle->beginScreening($reviewer, $application->fresh());
        $lifecycle->requestCorrection($reviewer, $application->fresh(), 'Clarify the name', [['target_kind' => 'question', 'form_question_id' => $question->id, 'issue_category' => 'clarification', 'instruction' => 'Update the name.']]);
        $working = $application->fresh()->currentWorkingRevision->draft;
        app(ApplicantDraftService::class)->save($school, $working, ['name' => 'SYNTHETIC-ANSWER-DISCLOSURE-PROBE'], 1, (string) Str::uuid());
        $lifecycle->submit($school, $application->fresh(), $application->fresh()->lock_version, 2, (string) Str::uuid());
        $before = $application->fresh()->submissions()->pluck('combined_hash', 'id')->all();
        $historyBefore = DB::table('application_state_history')->count();
        $reviewer->removeRole(AccountRole::Chedro->value);
        $reviewer->givePermissionTo(['platform.admin.access', 'applications.intake.view', 'evidence.reviews.view']);
        $reviewer->unsetRelation('permissions')->unsetRelation('roles');
        $this->actingAs($reviewer);
        foreach ([null, $old->public_id] as $copy) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $page = Livewire::actingAs($reviewer)->withQueryParams(['application' => $application->public_id, 'submission' => $copy])->test(SyntheticApplicationIntake::class);
            $page->assertSee($institution->name)->assertDontSee('SYNTHETIC-ANSWER-DISCLOSURE-PROBE')->assertDontSee('SYNTHETIC-HISTORY-ANSWER');
            $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($q) => str_starts_with(strtolower($q['query']), 'select') && str_contains($q['query'], 'application_submission_answers')), 'No answer SELECT may run without explicit permission.');
            DB::disableQueryLog();
            $serialized = json_encode([$page->snapshot, $page->effects], JSON_THROW_ON_ERROR);
            foreach (['SYNTHETIC-ANSWER-DISCLOSURE-PROBE', 'SYNTHETIC-HISTORY-ANSWER', 'SYNTHETIC-HIDDEN-ANSWER', 'current_submission', '"answers"'] as $secret) {
                $this->assertStringNotContainsString($secret, $serialized);
            }
            foreach (['selected', 'submittedCopy', 'applications', 'queueOptions', 'queueQuery'] as $helper) {
                $this->assertHelperHttpDenied($page, $helper);
            }
            $page->call('$refresh');
            $this->assertStringNotContainsString('SYNTHETIC-ANSWER-DISCLOSURE-PROBE', json_encode($page->effects));
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $evidence = Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class);
        foreach (['evidence_reference_revisions', 'synthetic_evidence_review_findings'] as $protectedTable) {
            $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($query) => str_starts_with(strtolower($query['query']), 'select') && str_contains($query['query'], $protectedTable)), 'A view-only reviewer must not load protected evidence/history relationships.');
        }
        DB::disableQueryLog();
        foreach (['selected', 'cases', 'queueOptions', 'queueQuery'] as $helper) {
            $this->assertHelperHttpDenied($evidence, $helper);
        }
        $this->assertStringNotContainsString('SYNTHETIC-HISTORY-ANSWER', json_encode([$evidence->snapshot, $evidence->effects]));
        $reviewer->givePermissionTo('applications.intake.answers.view');
        $reviewer->unsetRelation('permissions');
        $current = Livewire::actingAs($reviewer)->withQueryParams(['application' => $application->public_id])->test(SyntheticApplicationIntake::class)->assertSee('SYNTHETIC-ANSWER-DISCLOSURE-PROBE')->assertDontSee('SYNTHETIC-HIDDEN-ANSWER');
        Livewire::actingAs($reviewer)->withQueryParams(['application' => $application->public_id, 'submission' => $old->public_id])->test(SyntheticApplicationIntake::class)->assertSee('SYNTHETIC-HISTORY-ANSWER')->assertDontSee('SYNTHETIC-HIDDEN-ANSWER');
        $this->assertStringNotContainsString('SYNTHETIC-ANSWER-DISCLOSURE-PROBE', json_encode($current->snapshot));
        $reviewer->revokePermissionTo('applications.intake.answers.view');
        $reviewer->unsetRelation('permissions');
        $current->call('$refresh')->assertDontSee('SYNTHETIC-ANSWER-DISCLOSURE-PROBE');
        $this->assertStringNotContainsString('SYNTHETIC-ANSWER-DISCLOSURE-PROBE', json_encode($current->effects));
        $grant->update(['revoked_at' => now(), 'revoked_by' => $admin->id, 'revocation_reason' => 'SEC005 revoke']);
        $current->call('$refresh')->assertNotFound();
        $this->assertSame($before, $application->fresh()->submissions()->pluck('combined_hash', 'id')->all());
        $this->assertSame($historyBefore, DB::table('application_state_history')->count());
    }

    private function scenario(bool $multipleChoice = false): array
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form,$requirements,$question] = $this->configuration($osds, $multipleChoice);
        $pub = app(QuestionnairePublicationService::class);
        $manifest = $pub->publishApplicantSandbox($admin, $pub->review($admin, $pub->submitForReview($osds, $form, $requirements), true, 'APP-SBX'));
        $region = Region::query()->create(['public_id' => (string) Str::uuid(), 'code' => 'SYN', 'name' => 'Synthetic']);
        $institution = Institution::query()->create(['name' => 'Synthetic Association', 'primary_region_id' => $region->id, 'organization_type' => 'athletic_association', 'is_synthetic' => true]);
        $school = $this->user(AccountRole::School, true);
        InstitutionRepresentative::query()->create(['user_id' => $school->id, 'institution_id' => $institution->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::query()->create(['institution_id' => $institution->id, 'module_id' => $manifest->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Synthetic test']);

        return [$school, $institution, $manifest, $question];
    }

    public function test_database_expired_reviewer_assignments_never_authorize_queue_rows(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Synthetic time boundary probe'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay()->toIso8601String(), 'granted_by' => $admin->id, 'created_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String()]);
        foreach (['synthetic_application_reviewer_assignments', 'synthetic_evidence_reviewer_assignments'] as $table) {
            // Explicit-offset instants avoid the application's naive datetime binding under test.
            DB::table($table)->insert(['public_id' => (string) Str::uuid(), 'reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay()->toIso8601String(), 'ends_at' => now()->subMinute()->toIso8601String(), 'granted_by' => $admin->id, 'reason' => 'Isolated real expiry boundary probe', 'created_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String()]);
            $this->assertSame(1, DB::table($table)->where('reviewer_id', $reviewer->id)->whereRaw('ends_at <= CURRENT_TIMESTAMP')->count(), 'PostgreSQL confirms that this assignment has already expired.');
        }
        $scope = app(ReviewerQueueScope::class);
        $counts = [
            $scope->applications(Application::query()->whereNotNull('current_submission_id'), $reviewer)->count(),
            $scope->evidenceReviews(SyntheticEvidenceReviewCase::query(), $reviewer)->count(),
        ];
        $this->assertSame([0, 0], $counts, 'Neither queue may authorize an assignment already expired according to PostgreSQL CURRENT_TIMESTAMP.');
    }

    public function test_sec006_explicit_instant_assignment_boundary_matrix_and_livewire_expiry(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Boundary-only synthetic answer'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), $manifest->requirementSetVersion->requirements()->first(), 'https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view', 2, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $admin = $this->user(AccountRole::Admin, true);
        $anchor = CarbonImmutable::parse(DB::selectOne('SELECT CURRENT_TIMESTAMP AS instant')->instant)->startOfSecond();
        $this->travelTo($anchor);
        try {
            foreach (['expired', 'boundary', 'future', 'current', 'open', 'revoked'] as $state) {
                $reviewer = $this->user(AccountRole::Chedro, true);
                DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => $anchor->subDay()->toIso8601String(), 'granted_by' => $admin->id]);
                foreach (['synthetic_application_reviewer_assignments', 'synthetic_evidence_reviewer_assignments'] as $table) {
                    DB::table($table)->insert(['public_id' => (string) Str::uuid(), 'reviewer_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'module_id' => $manifest->module_id, 'starts_at' => ($state === 'future' ? $anchor->addMinute() : $anchor->subDay())->toIso8601String(), 'ends_at' => match ($state) {
                        'expired' => $anchor->subMinute()->toIso8601String(),'boundary' => $anchor->toIso8601String(),'open' => null,'future' => $anchor->addMinutes(2)->toIso8601String(),default => $anchor->addMinute()->toIso8601String()
                    }, 'revoked_at' => $state === 'revoked' ? $anchor->subSecond()->toIso8601String() : null, 'revoked_by' => $state === 'revoked' ? $admin->id : null, 'revocation_reason' => $state === 'revoked' ? 'Synthetic revocation' : null, 'granted_by' => $admin->id, 'reason' => 'Explicit clock boundary regression']);
                }
                $this->actingAs($reviewer);
                $effective = in_array($state, ['current', 'open'], true);
                foreach ([new SyntheticApplicationIntake, new SyntheticEvidenceReviews] as $page) {
                    $page->search = $institution->name;
                    $page->service = $manifest->module->code;
                    $records = $page instanceof SyntheticApplicationIntake ? $page->applications() : $page->cases();
                    $this->assertSame($effective ? 1 : 0, $records->total(), $state.' filtered queue count');
                    $this->assertCount($effective ? 1 : 0, $records->items());
                }
                $intake = Livewire::actingAs($reviewer)->withQueryParams(['application' => $application->public_id])->test(SyntheticApplicationIntake::class);
                $evidence = Livewire::actingAs($reviewer)->withQueryParams(['case' => $case->public_id])->test(SyntheticEvidenceReviews::class);
                if (! $effective) {
                    $intake->assertNotFound();
                    $evidence->assertForbidden();
                    $this->get('/admin/synthetic-application-intake?application='.$application->public_id)->assertNotFound();
                    $this->get('/admin/synthetic-evidence-reviews?case='.$case->public_id)->assertForbidden();
                } elseif ($state === 'current') {
                    $intake->assertSee('Boundary-only synthetic answer');
                    $evidence->assertSee('Undertaking');
                    $this->travelTo($anchor->addMinutes(2));
                    $intake->call('$refresh')->assertNotFound();
                    $evidence->call('$refresh')->assertForbidden();
                    $this->travelTo($anchor);
                }
            }
        } finally {
            $this->travelBack();
        }
    }

    public function test_sec006_real_expiry_of_regional_scope_representation_and_eligibility(): void
    {
        [$school,$institution,$manifest] = $this->scenario();
        $draft = app(ApplicantDraftService::class)->start($school, $institution, $manifest);
        $admin = $this->user(AccountRole::Admin, true);
        $reviewer = $this->user(AccountRole::Chedro, true);
        DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $institution->primary_region_id, 'starts_at' => now()->subDay()->toIso8601String(), 'ends_at' => now()->subMinute()->toIso8601String(), 'granted_by' => $admin->id]);
        $scope = app(ScopeAuthorizer::class);
        $this->assertFalse($scope->canAccessRegion($reviewer, $institution->primary_region_id));
        $this->assertSame(0, app(StaffListScope::class)->institutions(Institution::query(), $reviewer)->count());
        $editor = Livewire::actingAs($school)->test(DraftEditor::class, ['draft' => $draft])->assertSee('Organization name');
        DB::table('institution_representatives')->where('user_id', $school->id)->where('institution_id', $institution->id)->update(['ends_at' => now()->subMinute()->toIso8601String()]);
        $this->assertFalse($scope->hasEffectiveRepresentation($school, $institution->id));
        $editor->call('$refresh')->assertNotFound();
        $this->actingAs($school)->get(route('applicant.applications.show', $draft->application))->assertNotFound();
        $this->get('/applicant/drafts/'.$draft->public_id)->assertNotFound();
        DB::table('institution_representatives')->where('user_id', $school->id)->update(['ends_at' => now()->addMinute()->toIso8601String()]);
        $this->assertTrue($scope->hasEffectiveRepresentation($school, $institution->id));
        $this->get('/applicant/drafts/'.$draft->public_id)->assertOk();
        DB::table('applicant_sandbox_eligibility_grants')->where('institution_id', $institution->id)->update(['ends_at' => now()->subMinute()->toIso8601String()]);
        $this->get('/applicant/drafts/'.$draft->public_id)->assertNotFound();
    }

    private function configuration(User $owner, bool $multipleChoice = false): array
    {
        $module = OsdsModule::where('code', 'registration')->firstOrFail();
        $definition = FormDefinition::create(['module_id' => $module->id, 'key' => 'f_'.Str::random(5), 'name' => 'Synthetic', 'owner_id' => $owner->id]);
        $form = FormVersion::create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $section = FormSection::create(['form_version_id' => $form->id, 'key' => 'identity', 'title' => 'Identity', 'sort_order' => 1]);
        $question = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'name', 'type' => 'short_text', 'label' => 'Organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
        FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'private_notes', 'type' => 'short_text', 'label' => 'Private notes', 'is_required' => false, 'sort_order' => 2, 'validation_rules' => [], 'condition_rules' => ['question' => 'name', 'operator' => 'equals', 'value' => 'Show notes']]);
        if ($multipleChoice) {
            $multiple = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'programs', 'type' => 'multiple_choice', 'label' => 'Programs', 'is_required' => true, 'sort_order' => 3, 'validation_rules' => []]);
            $multiple->options()->create(['key' => 'primary', 'label' => 'Primary', 'sort_order' => 1]);
            $multiple->options()->create(['key' => 'secondary', 'label' => 'Secondary', 'sort_order' => 2]);
        }
        $requirements = RequirementSetVersion::create(['module_id' => $module->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $requirement = DocumentaryRequirement::create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Undertaking', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
        $requirement->providers()->attach(EvidenceProviderType::where('code', 'google_drive_file')->value('id'));

        return [$form, $requirements, $question];
    }

    private function user(AccountRole $role, bool $synthetic = false): User
    {
        $user = User::factory()->create(['status' => 'active', 'is_synthetic' => $synthetic]);
        $user->assignRole($role->value);

        return $user;
    }
}
