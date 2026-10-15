<?php

namespace Tests\Feature;

use App\Models\ApplicantSandboxEligibilityGrant;
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
use App\Models\PublicationManifestRetirement;
use App\Models\Region;
use App\Models\RequirementSetVersion;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\SyntheticEvidenceReviewFinding;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Evidence\EvidenceLocatorCipher;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\GoogleEvidenceLocatorParser;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicantSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
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
        $this->post(route('applicant.login.store'), ['email' => $regular->email, 'password' => $password])->assertForbidden();
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
        $encrypted = app(EvidenceLocatorCipher::class)->encrypt($parsed);
        $this->assertStringNotContainsString('AbCdEfGhIjKlMnOp', $encrypted['locator_ciphertext']);
        $this->assertSame($parsed->fileId, app(EvidenceLocatorCipher::class)->decrypt($encrypted['locator_ciphertext'], $encrypted['encryption_key_version'])->fileId);
        config(['evidence.keys.v1' => 'base64:'.base64_encode(str_repeat('a', 32)), 'evidence.keys.v2' => 'base64:'.base64_encode(str_repeat('b', 32)), 'evidence.current_key_version' => 'v1']);
        $old = app(EvidenceLocatorCipher::class)->encrypt($parsed);
        config(['evidence.current_key_version' => 'v2']);
        $this->assertSame($parsed->fileId, app(EvidenceLocatorCipher::class)->decrypt($old['locator_ciphertext'], 'v1')->fileId);
        $this->assertSame('v2', app(EvidenceLocatorCipher::class)->encrypt($parsed)['encryption_key_version']);
        foreach (['http://drive.google.com/file/d/AbCdEfGhIjKlMnOp', 'https://drive.google.com/drive/folders/AbCdEfGhIjKlMnOp', 'https://docs.google.com/forms/d/AbCdEfGhIjKlMnOp', 'https://docs.google.com/document/d/AbCdEfGhIjKlMnOp/edit#secret'] as $bad) {
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

    private function scenario(): array
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form,$requirements,$question] = $this->configuration($osds);
        $pub = app(QuestionnairePublicationService::class);
        $manifest = $pub->publishApplicantSandbox($admin, $pub->review($admin, $pub->submitForReview($osds, $form, $requirements), true, 'APP-SBX'));
        $region = Region::query()->create(['public_id' => (string) Str::uuid(), 'code' => 'SYN', 'name' => 'Synthetic']);
        $institution = Institution::query()->create(['name' => 'Synthetic Association', 'primary_region_id' => $region->id, 'organization_type' => 'athletic_association', 'is_synthetic' => true]);
        $school = $this->user(AccountRole::School, true);
        InstitutionRepresentative::query()->create(['user_id' => $school->id, 'institution_id' => $institution->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::query()->create(['institution_id' => $institution->id, 'module_id' => $manifest->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Synthetic test']);

        return [$school, $institution, $manifest, $question];
    }

    private function configuration(User $owner): array
    {
        $module = OsdsModule::where('code', 'registration')->firstOrFail();
        $definition = FormDefinition::create(['module_id' => $module->id, 'key' => 'f_'.Str::random(5), 'name' => 'Synthetic', 'owner_id' => $owner->id]);
        $form = FormVersion::create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $section = FormSection::create(['form_version_id' => $form->id, 'key' => 'identity', 'title' => 'Identity', 'sort_order' => 1]);
        $question = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'name', 'type' => 'short_text', 'label' => 'Organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
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
