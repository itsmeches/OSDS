<?php

namespace App\Services\Questionnaires;

use App\Models\FormDefinition;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Support\FoundationPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionnaireDraftService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function cloneForm(User $actor, FormVersion $source): FormVersion
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $source) {
            $source->load('sections.questions.options');
            $version = FormVersion::query()->create(['form_definition_id' => $source->form_definition_id, 'version_number' => ((int) FormVersion::query()->where('form_definition_id', $source->form_definition_id)->max('version_number')) + 1, 'state' => 'draft', 'created_by' => $actor->id]);
            foreach ($source->sections as $sourceSection) {
                $section = $version->sections()->create($sourceSection->only(['key', 'title', 'description', 'sort_order']));
                foreach ($sourceSection->questions as $sourceQuestion) {
                    $question = $section->questions()->create(array_merge($sourceQuestion->only(['key', 'type', 'label', 'help_text', 'is_required', 'sort_order', 'validation_rules', 'condition_rules']), ['form_version_id' => $version->id]));
                    foreach ($sourceQuestion->options as $option) {
                        $question->options()->create($option->only(['key', 'label', 'sort_order']));
                    }
                }
            }
            $this->audit->record('questionnaire.form_cloned', 'success', $actor, $version, metadata: ['source_version_id' => $source->id]);

            return $version;
        });
    }

    public function createPair(User $actor, OsdsModule $module, string $title): array
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $module, $title): array {
            $base = Str::slug($title, '_') ?: 'questionnaire';
            $key = $base;
            $suffix = 2;
            while (FormDefinition::query()->where('module_id', $module->id)->where('key', $key)->exists()) {
                $key = $base.'_'.$suffix++;
            }
            $definition = FormDefinition::query()->create(['module_id' => $module->id, 'key' => $key, 'name' => trim($title), 'owner_id' => $actor->id]);
            $form = FormVersion::query()->create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $actor->id]);
            $form->sections()->create(['key' => 'main_section', 'title' => 'Main section', 'description' => null, 'sort_order' => 1]);
            $requirements = RequirementSetVersion::query()->create([
                'module_id' => $module->id,
                'version_number' => ((int) RequirementSetVersion::query()->where('module_id', $module->id)->max('version_number')) + 1,
                'state' => 'draft', 'created_by' => $actor->id,
            ]);
            $this->audit->record('questionnaire.draft_pair_created', 'success', $actor, $form, metadata: ['module_id' => $module->id, 'requirement_set_version_id' => $requirements->id]);

            return [$form, $requirements];
        });
    }

    public function cloneRequirements(User $actor, RequirementSetVersion $source): RequirementSetVersion
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $source) {
            $source->load('requirements.providers');
            $version = RequirementSetVersion::query()->create(['module_id' => $source->module_id, 'version_number' => ((int) RequirementSetVersion::query()->where('module_id', $source->module_id)->max('version_number')) + 1, 'state' => 'draft', 'created_by' => $actor->id]);
            foreach ($source->requirements as $sourceRequirement) {
                $requirement = $version->requirements()->create($sourceRequirement->only(['key', 'label', 'instructions', 'is_required', 'minimum_evidence', 'maximum_evidence', 'sort_order', 'condition_rules']));
                $requirement->providers()->sync($sourceRequirement->providers->pluck('id'));
            }
            $this->audit->record('questionnaire.requirements_cloned', 'success', $actor, $version, metadata: ['source_version_id' => $source->id]);

            return $version;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->isActive() || ! $actor->can(FoundationPermission::ManageQuestionnaireDrafts->value)) {
            throw new AuthorizationException;
        }
    }
}
