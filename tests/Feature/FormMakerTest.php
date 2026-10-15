<?php

namespace Tests\Feature;

use App\Filament\Pages\FormBuilder;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FormMakerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    public function test_osds_can_open_visual_builder_and_unauthorized_roles_cannot(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $url = FormBuilder::getUrl(['form' => $form->id, 'requirements' => $requirements->id]);

        $this->actingAs($osds)->get($url)->assertOk()->assertSee('Build questionnaire')->assertSee('Review and test')->assertSee('Save draft');
        $this->actingAs($this->user(AccountRole::Admin))->get($url)->assertForbidden();
        $this->actingAs($this->user(AccountRole::School))->get($url)->assertForbidden();
    }

    public function test_authoring_save_uses_lock_versions_and_rejects_stale_writer(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $service = app(QuestionnaireAuthoringService::class);
        $state = $this->state();

        [$formLock, $requirementLock] = $service->save($osds, $form, $requirements, $state, 0, 0);
        $this->assertSame(1, $formLock);
        $this->assertSame(1, $requirementLock);
        $this->assertDatabaseHas('form_questions', ['form_version_id' => $form->id, 'key' => 'contact_email', 'label' => 'Contact email']);

        $this->expectException(ValidationException::class);
        $service->save($osds, $form, $requirements, $state, 0, 0);
    }

    public function test_visual_builder_saves_a_valid_loaded_draft(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->call('save')
            ->assertSet('saveState', 'Saved')
            ->assertSet('formLock', 1)
            ->assertSet('requirementLock', 1)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('form_questions', [
            'form_version_id' => $form->id,
            'key' => 'organization_name',
        ]);
    }

    public function test_visual_builder_explains_validation_failure_and_preserves_unsaved_input(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->set('documentRequirements.0.label', 'Changed but unsaved')
            ->set('documentRequirements.0.provider_ids', [])
            ->call('save')
            ->assertSet('saveState', 'Save failed')
            ->assertSet('documentRequirements.0.label', 'Changed but unsaved')
            ->assertHasErrors(['documentRequirements.0.provider_ids'])
            ->assertSee('The draft was not saved')
            ->assertSee('Your unsaved changes are still available');

        $this->assertDatabaseHas('documentary_requirements', [
            'requirement_set_version_id' => $requirements->id,
            'label' => 'Undertaking',
        ]);
        $this->assertSame(0, $form->fresh()->lock_version);
        $this->assertSame(0, $requirements->fresh()->lock_version);
    }

    public function test_preview_changes_do_not_create_applicant_records(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->set('previewOpen', true)
            ->set('previewTesting', true)
            ->set('previewAnswers.organization_name', 'Temporary answer')
            ->assertSet('previewAnswers.organization_name', 'Temporary answer');

        $this->assertDatabaseCount('application_drafts', 0);
        $this->assertDatabaseCount('application_draft_answers', 0);
    }

    public function test_switching_questions_preview_and_stages_preserves_unsaved_input(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        $component = Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->call('addQuestion', 0);

        $firstKey = $component->get('sections.0.questions.0.key');
        $secondKey = $component->get('sections.0.questions.1.key');

        $component
            ->set('sections.0.questions.0.label', 'Locally edited organization name')
            ->call('selectQuestion', $secondKey)
            ->call('selectQuestion', $firstKey)
            ->assertSet('sections.0.questions.0.label', 'Locally edited organization name')
            ->assertSet('saveState', 'Unsaved changes')
            ->call('togglePreview')
            ->call('togglePreview')
            ->call('showStage', 'documents')
            ->set('documentRequirements.0.label', 'Locally edited undertaking')
            ->call('showStage', 'review')
            ->call('showStage', 'documents')
            ->assertSet('documentRequirements.0.label', 'Locally edited undertaking')
            ->assertSet('saveState', 'Unsaved changes');

        $this->assertDatabaseMissing('form_questions', [
            'form_version_id' => $form->id,
            'label' => 'Locally edited organization name',
        ]);
    }

    public function test_selected_question_remains_selected_after_reordering(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        $component = Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->call('addQuestion', 0);

        $selectedKey = $component->get('sections.0.questions.1.key');

        $component
            ->call('selectQuestion', $selectedKey)
            ->call('moveQuestion', 0, 1, -1)
            ->assertSet('selectedQuestionKey', $selectedKey)
            ->assertSet('sections.0.questions.0.key', $selectedKey);
    }

    public function test_saved_draft_can_be_validated_and_sent_for_admin_review_in_one_action(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->call('save')
            ->assertSet('saveState', 'Saved')
            ->assertSet('ready', false)
            ->call('submitForReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('configuration_review_requests', [
            'form_version_id' => $form->id,
            'requirement_set_version_id' => $requirements->id,
            'state' => 'pending',
        ]);
        $this->assertSame('in_review', $form->fresh()->state);
        $this->assertSame('in_review', $requirements->fresh()->state);
    }

    public function test_unsaved_draft_cannot_be_sent_for_admin_review(): void
    {
        [$osds, $form, $requirements] = $this->configuration();

        Livewire::actingAs($osds)
            ->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->set('sections.0.questions.0.label', 'Unsaved label')
            ->call('submitForReview')
            ->assertHasErrors(['review']);

        $this->assertDatabaseCount('configuration_review_requests', 0);
        $this->assertSame('draft', $form->fresh()->state);
    }

    public function test_published_configuration_cannot_be_saved_through_authoring_service(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $form->update(['state' => 'in_review']);
        $requirements->update(['state' => 'in_review']);

        $this->expectException(ValidationException::class);
        app(QuestionnaireAuthoringService::class)->save($osds, $form, $requirements, $this->state(), 0, 0);
    }

    private function configuration(): array
    {
        $osds = $this->user(AccountRole::Osds);
        $module = OsdsModule::query()->where('code', 'registration')->firstOrFail();
        $definition = FormDefinition::query()->create(['module_id' => $module->id, 'key' => 'maker_test', 'name' => 'Maker test', 'owner_id' => $osds->id]);
        $form = FormVersion::query()->create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $osds->id]);
        $section = FormSection::query()->create(['form_version_id' => $form->id, 'key' => 'organization', 'title' => 'Organization', 'sort_order' => 1]);
        FormQuestion::query()->create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'organization_name', 'type' => 'short_text', 'label' => 'Organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
        $requirements = RequirementSetVersion::query()->create(['module_id' => $module->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $osds->id]);
        $document = DocumentaryRequirement::query()->create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Undertaking', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
        $document->providers()->attach(EvidenceProviderType::query()->where('code', 'google_drive_file')->value('id'));

        return [$osds, $form, $requirements];
    }

    private function state(): array
    {
        return [
            'title' => 'Maker test',
            'sections' => [['key' => 'organization', 'title' => 'Organization', 'description' => '', 'questions' => [[
                'key' => 'contact_email', 'type' => 'email', 'label' => 'Contact email', 'help_text' => '', 'is_required' => true,
                'validation_rules' => [], 'condition_rules' => null, 'options' => [],
            ]]]],
            'requirements' => [['key' => 'undertaking', 'label' => 'Undertaking', 'instructions' => '', 'is_required' => true,
                'minimum_evidence' => 1, 'maximum_evidence' => 1,
                'provider_ids' => [EvidenceProviderType::query()->where('code', 'google_drive_file')->value('id')], 'condition_rules' => null]],
        ];
    }

    private function user(AccountRole $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role->value);

        return $user;
    }
}
