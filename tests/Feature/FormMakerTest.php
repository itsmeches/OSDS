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
use App\Services\Questionnaires\ConditionalLogic;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('existingConditionShapes')]
    public function test_noop_builder_save_preserves_supported_document_conditions(array $rules): void
    {
        [$osds,$form,$requirements] = $this->configuration();
        $requirements->requirements()->firstOrFail()->update(['condition_rules' => $rules]);
        // Compare exact PostgreSQL JSONB structure before/after, including list order and scalar types.
        $rules = $requirements->requirements()->firstOrFail()->condition_rules;
        Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->call('save')->assertSet('saveState', 'Saved')->assertHasNoErrors();
        $saved = $requirements->requirements()->where('key', 'undertaking')->firstOrFail()->condition_rules;
        $this->assertSame($rules, $saved, 'Saving an unchanged supported condition must not silently remove or simplify it.');
    }

    public static function existingConditionShapes(): array
    {
        return [
            'standalone leaf' => [['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Alpha']],
            'two all rules' => [['all' => [['question' => 'organization_name', 'operator' => 'is_not_empty'], ['question' => 'organization_name', 'operator' => 'not_equals', 'value' => 'Blocked']]]],
            'two any rules' => [['any' => [['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Alpha'], ['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Beta']]]],
            'nested rules' => [['all' => [['any' => [['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Alpha'], ['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Beta']]], ['question' => 'organization_name', 'operator' => 'not_equals', 'value' => 'Blocked']]]],
            'typed null' => [['question' => 'organization_name', 'operator' => 'equals', 'value' => null]],
            'set operator' => [['question' => 'organization_name', 'operator' => 'in', 'value' => ['Alpha', 'Beta']]],
        ];
    }

    #[DataProvider('existingConditionShapes')]
    public function test_question_and_document_conditions_survive_navigation_unrelated_edits_and_reload(array $rules): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $source = $form->questions()->firstOrFail();
        $target = $source->replicate();
        $target->fill(['key' => 'dependent', 'label' => 'Dependent question', 'sort_order' => 2, 'condition_rules' => $rules])->save();
        $requirements->requirements()->firstOrFail()->update(['condition_rules' => $rules]);
        $rules = $requirements->requirements()->firstOrFail()->condition_rules;
        $engine = app(ConditionalLogic::class);
        foreach ([false, true] as $reload) {
            $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
            foreach (['Alpha', 'Beta', 'Blocked', ''] as $answer) {
                $component->set('previewAnswers.organization_name', $answer);
                $expected = $engine->evaluate($rules, ['organization_name' => $answer]);
                $this->assertSame($expected, $component->instance()->visible($component->get('sections.0.questions.1')));
                $this->assertSame($expected, $component->instance()->visibleRequirement($component->get('documentRequirements.0')));
            }
            $component->call('showStage', 'documents')->call('showStage', 'review')->call('showStage', 'build')
                ->set('formTitle', $reload ? 'Second saved title' : 'First saved title')
                ->set('sections.0.questions.0.help_text', 'Unrelated instruction')
                ->set('documentRequirements.0.instructions', 'Unrelated document instruction')
                ->call('save')->assertSet('saveState', 'Saved')->assertHasNoErrors();
            $questionRules = $form->questions()->where('key', 'dependent')->firstOrFail()->condition_rules;
            $documentRules = $requirements->requirements()->firstOrFail()->condition_rules;
            $this->assertSame($rules, $questionRules);
            $this->assertSame($rules, $documentRules);
            foreach (['Alpha', 'Beta', 'Blocked', ''] as $answer) {
                $this->assertSame($engine->evaluate($rules, ['organization_name' => $answer]), $engine->evaluate($questionRules, ['organization_name' => $answer]));
                $this->assertSame($engine->evaluate($rules, ['organization_name' => $answer]), $engine->evaluate($documentRules, ['organization_name' => $answer]));
            }
        }
    }

    public function test_simple_condition_edits_and_explicit_clear_preserve_other_rules(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $original = ['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Alpha'];
        $requirements->requirements()->firstOrFail()->update(['condition_rules' => $original]);
        $original = $requirements->requirements()->firstOrFail()->condition_rules;
        $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
        $component->set('documentRequirements.0.condition_value', 'Beta')->call('save')->assertHasNoErrors()->assertSet('saveState', 'Saved');
        $this->assertSame(array_replace($original, ['value' => 'Beta']), $requirements->requirements()->firstOrFail()->condition_rules);
        $component->set('documentRequirements.0.condition_source', '')->call('save')->assertSet('saveState', 'Save failed')->assertHasErrors(['conditions']);
        $this->assertSame('Beta', $requirements->requirements()->firstOrFail()->condition_rules['value']);
        $component->call('clearCondition', 'requirement', 'undertaking')->call('save')->assertHasNoErrors()->assertSet('saveState', 'Saved');
        $this->assertNull($requirements->requirements()->firstOrFail()->condition_rules);
    }

    public function test_protected_condition_cannot_be_replaced_by_forged_projection_or_raw_rule(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $rules = self::existingConditionShapes()['two any rules'][0];
        $requirements->requirements()->firstOrFail()->update(['condition_rules' => $rules]);
        $rules = $requirements->requirements()->firstOrFail()->condition_rules;
        $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
        $component->set('documentRequirements.0.condition_rules', null)->call('save')->assertHasNoErrors();
        $this->assertSame($rules, $requirements->requirements()->firstOrFail()->condition_rules);
        $component->set('documentRequirements.0.condition_source', 'organization_name')->call('save')->assertSet('saveState', 'Save failed')->assertHasErrors(['conditions']);
        $this->assertSame($rules, $requirements->requirements()->firstOrFail()->condition_rules);
        $component->call('clearCondition', 'requirement', 'undertaking')->call('save')->assertHasNoErrors();
        $this->assertNull($requirements->requirements()->firstOrFail()->condition_rules);
    }

    public function test_condition_source_removal_and_reordering_fail_without_partial_save(): void
    {
        [$osds, $form, $requirements] = $this->configuration();
        $source = $form->questions()->firstOrFail();
        $rules = ['question' => 'organization_name', 'operator' => 'is_not_empty'];
        $target = $source->replicate();
        $target->fill(['key' => 'dependent', 'label' => 'Dependent', 'sort_order' => 2, 'condition_rules' => $rules])->save();
        $rules = $target->fresh()->condition_rules;
        $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
        $component->call('moveQuestion', 0, 0, 1)->call('save')->assertSet('saveState', 'Save failed');
        $this->assertSame(0, $form->fresh()->lock_version);
        $this->assertSame($rules, $target->fresh()->condition_rules);
        $component->call('removeQuestion', 0, 1)->call('save')->assertSet('saveState', 'Save failed');
        $this->assertSame(2, $form->questions()->count());
        $this->assertSame(0, $requirements->fresh()->lock_version);
    }

    public function test_question_condition_edit_clear_invalid_reference_and_stale_copy(): void
    {
        [$osds,$form,$requirements] = $this->configuration();
        $source = $form->questions()->firstOrFail();
        $target = $source->replicate();
        $target->fill(['key' => 'dependent', 'label' => 'Dependent', 'sort_order' => 2, 'condition_rules' => ['all' => [['question' => 'organization_name', 'operator' => 'equals', 'value' => 'Alpha']]]])->save();
        $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
        $stale = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
        $component->set('sections.0.questions.1.condition_value', 'Beta')->call('save')->assertHasNoErrors();
        $this->assertSame('Beta', $form->questions()->where('key', 'dependent')->firstOrFail()->condition_rules['all'][0]['value']);
        $stale->set('formTitle', 'Unsaved stale title')->call('save')->assertSet('saveState', 'Conflict')->assertSet('formTitle', 'Unsaved stale title');
        $this->assertSame(1, $form->fresh()->lock_version);
        $component->set('sections.0.questions.1.condition_source', 'missing')->call('save')->assertSet('saveState', 'Save failed')->assertHasErrors(['conditions']);
        $this->assertSame(1, $form->fresh()->lock_version);
        $component->call('clearCondition', 'question', 'dependent')->call('save')->assertHasNoErrors();
        $this->assertNull($form->questions()->where('key', 'dependent')->firstOrFail()->condition_rules);
    }

    public function test_builder_livewire_helpers_and_new_condition_actions_reauthorize_after_permission_revocation(): void
    {
        [$osds,$form,$requirements] = $this->configuration();
        $form->questions()->firstOrFail()->update(['label' => 'STAFF_CONFIG_ONLY_EINT002']);
        foreach (['selectedQuestionSelection', 'selectedRequirementSelection', 'check', 'save', 'clearCondition'] as $method) {
            $osds->assignRole(AccountRole::Osds->value);
            $component = Livewire::actingAs($osds->fresh())->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id]);
            $snapshot = $component->snapshot;
            $osds->removeRole(AccountRole::Osds->value);
            Livewire::actingAs($osds->fresh());
            $args = $method === 'clearCondition' ? ['requirement', 'undertaking'] : [];
            $component->call($method, ...$args)->assertForbidden();
            config(['app.debug' => false]);
            $this->postJson(app('livewire')->getUpdateUri(), ['components' => [['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'updates' => (object) [], 'calls' => [['method' => $method, 'params' => $args]]]]], ['X-Livewire' => 'true'])
                ->assertForbidden()->assertDontSee('STAFF_CONFIG_ONLY_EINT002');
        }
        $this->assertSame(0, $form->fresh()->lock_version);
        $this->assertSame(0, $requirements->fresh()->lock_version);
    }

    public function test_authoring_preserves_exact_labels_and_free_text(): void
    {
        [$osds,$form,$requirements] = $this->configuration();
        $state = $this->state();
        $state['title'] = '  Exact title  ';
        $state['sections'][0]['title'] = '  Exact section  ';
        $state['sections'][0]['questions'][0]['label'] = '  Exact_Case / label  ';
        $state['sections'][0]['questions'][0]['help_text'] = " First line\n  Second line ";
        $state['sections'][0]['questions'][0]['type'] = 'single_choice';
        $state['sections'][0]['questions'][0]['options'] = [['key' => 'one', 'label' => '  Choice_ONE  '], ['key' => 'two', 'label' => 'Choice Two']];
        $state['requirements'][0]['label'] = '  Exact Document  ';
        app(QuestionnaireAuthoringService::class)->save($osds, $form, $requirements, $state, 0, 0);
        $saved = $form->questions()->firstOrFail();
        $this->assertSame($state['sections'][0]['questions'][0]['label'], $saved->label);
        $this->assertSame($state['sections'][0]['questions'][0]['help_text'], $saved->help_text);
        $this->assertSame('  Choice_ONE  ', $saved->options()->firstOrFail()->label);
        $this->assertSame('  Exact Document  ', $requirements->requirements()->firstOrFail()->label);
        $this->assertSame('  Exact section  ', $form->sections()->firstOrFail()->title);
        $this->assertSame('  Exact title  ', $form->fresh()->definition->name);
    }

    public function test_unexpected_builder_save_failure_retains_input_and_redacts_diagnostics(): void
    {
        [$osds,$form,$requirements] = $this->configuration();
        Log::spy();
        $this->mock(QuestionnaireAuthoringService::class, fn ($mock) => $mock->shouldReceive('save')->once()->andThrow(new \RuntimeException('PRIVATE_SQL_BINDINGS_AND_LOCATOR')));
        $component = Livewire::actingAs($osds)->test(FormBuilder::class, ['form' => $form->id, 'requirements' => $requirements->id])
            ->set('sections.0.questions.0.label', 'Local retained title')->call('save')->assertSet('saveState', 'Not confirmed')
            ->assertSet('sections.0.questions.0.label', 'Local retained title')->assertHasErrors(['builder']);
        $this->assertStringNotContainsString('PRIVATE_SQL_BINDINGS_AND_LOCATOR', json_encode([$component->snapshot, $component->effects, $component->html(false)], JSON_THROW_ON_ERROR));
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Questionnaire save not confirmed' && array_keys($context) === ['reference', 'exception_type'] && $context['exception_type'] === \RuntimeException::class);
        $this->assertSame(0, $form->fresh()->lock_version);
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
