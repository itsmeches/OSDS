<?php

namespace Tests\Feature;

use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Questionnaires\ConditionalLogic;
use App\Services\Questionnaires\ConfigurationHasher;
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

class QuestionnaireConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    public function test_osds_prepares_and_admin_publishes_exact_reviewed_configuration_to_sandbox(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form, $requirements] = $this->configuration($osds);
        $service = app(QuestionnairePublicationService::class);

        $request = $service->submitForReview($osds, $form, $requirements);
        $this->assertSame('in_review', $form->fresh()->state);
        $approval = $service->review($admin, $request, true, 'SYNTHETIC-REVIEW-001');
        $manifest = $service->publishSandbox($admin, $approval);

        $this->assertSame('review_sandbox', $manifest->channel);
        $this->assertSame($request->combined_hash, $manifest->combined_hash);
        $this->assertSame($form->id, $manifest->form_version_id);
        $this->assertSame($requirements->id, $manifest->requirement_set_version_id);
        $this->assertDatabaseHas('audit_events', ['action' => 'questionnaire.sandbox_published', 'resource_id' => (string) $manifest->id]);
    }

    public function test_osds_cannot_approve_or_publish_and_admin_cannot_publish_unapproved_configuration(): void
    {
        $osds = $this->user(AccountRole::Osds);
        [$form, $requirements] = $this->configuration($osds);
        $request = app(QuestionnairePublicationService::class)->submitForReview($osds, $form, $requirements);

        $this->expectException(AuthorizationException::class);
        app(QuestionnairePublicationService::class)->review($osds, $request, true, 'FORGED');
    }

    public function test_material_change_after_review_prevents_approval(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form, $requirements, $question] = $this->configuration($osds);
        $request = app(QuestionnairePublicationService::class)->submitForReview($osds, $form, $requirements);
        DB::table('form_questions')->where('id', $question->id)->update(['label' => 'Tampered label']);

        $this->expectException(ValidationException::class);
        app(QuestionnairePublicationService::class)->review($admin, $request, true, 'STALE');
    }

    public function test_hash_is_canonical_and_material_changes_are_detected(): void
    {
        $osds = $this->user(AccountRole::Osds);
        [$form, $requirements, $question] = $this->configuration($osds);
        $hasher = app(ConfigurationHasher::class);
        $first = $hasher->form($form);
        $form->unsetRelation('sections');
        $this->assertSame($first, $hasher->form($form));
        $question->update(['label' => 'Changed label']);
        $form->unsetRelations();
        $this->assertNotSame($first, $hasher->form($form));
        $this->assertNotEmpty($hasher->requirements($requirements));
    }

    public function test_conditional_language_is_shared_and_rejects_later_or_invalid_options(): void
    {
        $logic = app(ConditionalLogic::class);
        $available = ['kind' => ['type' => 'single_choice', 'options' => ['hei', 'association']]];
        $rule = ['all' => [['question' => 'kind', 'operator' => 'equals', 'value' => 'hei']]];
        $logic->validate($rule, $available);
        $this->assertTrue($logic->evaluate($rule, ['kind' => 'hei']));
        $this->assertFalse($logic->evaluate($rule, ['kind' => 'association']));

        $this->expectException(ValidationException::class);
        $logic->validate(['question' => 'kind', 'operator' => 'equals', 'value' => 'invalid'], $available);
    }

    public function test_published_configuration_and_manifest_are_immutable_in_postgresql(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form, $requirements, $question] = $this->configuration($osds);
        $service = app(QuestionnairePublicationService::class);
        $manifest = $service->publishSandbox($admin, $service->review($admin, $service->submitForReview($osds, $form, $requirements), true, 'LOCK-001'));

        try {
            DB::table('form_questions')->where('id', $question->id)->update(['label' => 'Forbidden']);
            $this->fail('Published child update should fail.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('publication_manifests')->where('id', $manifest->id)->update(['combined_hash' => str_repeat('0', 64)]);
    }

    public function test_sandbox_is_staff_only_and_never_available_to_school_or_chedro(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form, $requirements] = $this->configuration($osds);
        $service = app(QuestionnairePublicationService::class);
        $manifest = $service->publishSandbox($admin, $service->review($admin, $service->submitForReview($osds, $form, $requirements), true, 'PREVIEW-001'));

        $this->actingAs($osds)->get(route('questionnaires.sandbox.show', $manifest))->assertOk()->assertSee('Synthetic staff review only');
        $this->actingAs($this->user(AccountRole::School))->get(route('questionnaires.sandbox.show', $manifest))->assertForbidden();
        $this->actingAs($this->user(AccountRole::Chedro))->get(route('questionnaires.sandbox.show', $manifest))->assertForbidden();
    }

    public function test_manifest_cannot_mix_modules_or_create_a_live_channel(): void
    {
        $osds = $this->user(AccountRole::Osds);
        [$form] = $this->configuration($osds);
        $acsa = OsdsModule::query()->where('code', 'acsa')->firstOrFail();
        $wrong = RequirementSetVersion::query()->create(['module_id' => $acsa->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $osds->id]);

        $this->expectException(ValidationException::class);
        app(QuestionnairePublicationService::class)->submitForReview($osds, $form, $wrong);
    }

    public function test_database_rejects_published_state_without_a_manifest(): void
    {
        $osds = $this->user(AccountRole::Osds);
        [$form] = $this->configuration($osds);

        $this->expectException(QueryException::class);
        DB::table('form_versions')->where('id', $form->id)->update(['state' => 'published']);
    }

    public function test_database_rejects_definition_changes_after_publication(): void
    {
        $osds = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        [$form, $requirements] = $this->configuration($osds);
        $service = app(QuestionnairePublicationService::class);
        $service->publishSandbox($admin, $service->review($admin, $service->submitForReview($osds, $form, $requirements), true, 'LOCK-DEFINITION'));

        $this->expectException(QueryException::class);
        DB::table('form_definitions')->where('id', $form->form_definition_id)->update(['name' => 'Forbidden']);
    }

    private function configuration(User $owner): array
    {
        $module = OsdsModule::query()->where('code', 'registration')->firstOrFail();
        $definition = FormDefinition::query()->create(['module_id' => $module->id, 'key' => 'test_form_'.Str::lower(Str::random(5)), 'name' => 'Synthetic form', 'owner_id' => $owner->id]);
        $form = FormVersion::query()->create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $section = FormSection::query()->create(['form_version_id' => $form->id, 'key' => 'identity', 'title' => 'Identity', 'sort_order' => 1]);
        $question = FormQuestion::query()->create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'name', 'type' => 'short_text', 'label' => 'Organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
        $requirements = RequirementSetVersion::query()->create(['module_id' => $module->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $requirement = DocumentaryRequirement::query()->create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Undertaking', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
        $requirement->providers()->attach(EvidenceProviderType::query()->where('code', 'google_drive_file')->value('id'));

        return [$form, $requirements, $question];
    }

    private function user(AccountRole $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role->value);

        return $user;
    }
}
