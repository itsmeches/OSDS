<?php

namespace App\Services\Questionnaires;

use App\Models\FormVersion;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Support\FoundationPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuestionnaireAuthoringService
{
    private const TYPES = ['short_text', 'long_text', 'telephone', 'email', 'date', 'integer', 'decimal', 'single_choice', 'multiple_choice', 'yes_no', 'declaration'];

    public function __construct(private readonly AuditRecorder $audit) {}

    public function save(User $actor, FormVersion $form, RequirementSetVersion $requirements, array $state, int $expectedFormLock, int $expectedRequirementLock): array
    {
        $this->authorize($actor);
        $this->validateState($state);

        return DB::transaction(function () use ($actor, $form, $requirements, $state, $expectedFormLock, $expectedRequirementLock): array {
            $form = FormVersion::query()->lockForUpdate()->with('definition')->findOrFail($form->id);
            $requirements = RequirementSetVersion::query()->lockForUpdate()->findOrFail($requirements->id);
            if ($form->state !== 'draft' || $requirements->state !== 'draft') {
                throw ValidationException::withMessages(['builder' => 'Only draft configurations can be edited.']);
            }
            if ($form->definition->module_id !== $requirements->module_id) {
                throw ValidationException::withMessages(['builder' => 'The questionnaire and document requirements belong to different programs.']);
            }
            if ($form->lock_version !== $expectedFormLock || $requirements->lock_version !== $expectedRequirementLock) {
                throw ValidationException::withMessages(['conflict' => 'This draft was updated in another browser tab. Your unsaved changes are still available.']);
            }

            if (isset($state['title']) && ! $form->definition->versions()->whereIn('state', ['published', 'retired'])->exists()) {
                $form->definition->update(['name' => trim($state['title'])]);
            }

            $form->questions()->with('options')->get()->each(fn ($question) => $question->options()->delete());
            $form->questions()->delete();
            $form->sections()->delete();
            foreach (array_values($state['sections']) as $sectionIndex => $sectionData) {
                $section = $form->sections()->create([
                    'key' => $sectionData['key'], 'title' => trim($sectionData['title']),
                    'description' => $sectionData['description'] ?: null, 'sort_order' => $sectionIndex + 1,
                ]);
                foreach (array_values($sectionData['questions']) as $questionIndex => $questionData) {
                    $question = $section->questions()->create([
                        'form_version_id' => $form->id, 'key' => $questionData['key'], 'type' => $questionData['type'],
                        'label' => trim($questionData['label']), 'help_text' => $questionData['help_text'] ?: null,
                        'is_required' => (bool) $questionData['is_required'], 'sort_order' => $questionIndex + 1,
                        'validation_rules' => $questionData['validation_rules'] ?? [], 'condition_rules' => $questionData['condition_rules'] ?? null,
                    ]);
                    foreach (array_values($questionData['options'] ?? []) as $optionIndex => $optionData) {
                        $question->options()->create(['key' => $optionData['key'], 'label' => trim($optionData['label']), 'sort_order' => $optionIndex + 1]);
                    }
                }
            }

            $requirements->requirements()->delete();
            foreach (array_values($state['requirements']) as $requirementIndex => $requirementData) {
                $requirement = $requirements->requirements()->create([
                    'key' => $requirementData['key'], 'label' => trim($requirementData['label']),
                    'instructions' => $requirementData['instructions'] ?: null,
                    'is_required' => (bool) $requirementData['is_required'],
                    'minimum_evidence' => (int) $requirementData['minimum_evidence'],
                    'maximum_evidence' => (int) $requirementData['maximum_evidence'],
                    'sort_order' => $requirementIndex + 1, 'condition_rules' => $requirementData['condition_rules'] ?? null,
                ]);
                $requirement->providers()->sync(array_values($requirementData['provider_ids'] ?? []));
            }

            $form->update(['lock_version' => $form->lock_version + 1, 'configuration_hash' => null]);
            $requirements->update(['lock_version' => $requirements->lock_version + 1, 'configuration_hash' => null]);
            $this->audit->record('questionnaire.draft_saved', 'success', $actor, $form, metadata: [
                'form_version_id' => $form->id, 'requirement_set_version_id' => $requirements->id,
                'form_lock_version' => $form->lock_version, 'requirement_lock_version' => $requirements->lock_version,
            ]);

            return [$form->lock_version, $requirements->lock_version];
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->isActive() || ! $actor->can(FoundationPermission::ManageQuestionnaireDrafts->value)) {
            throw new AuthorizationException;
        }
    }

    private function validateState(array $state): void
    {
        Validator::make($state, [
            'title' => ['required', 'string', 'max:255'],
            'sections' => ['required', 'array', 'min:1'], 'sections.*.key' => ['required', 'alpha_dash', 'distinct'],
            'sections.*.title' => ['required', 'string', 'max:255'], 'sections.*.description' => ['nullable', 'string', 'max:4000'],
            'sections.*.questions' => ['required', 'array', 'min:1'], 'sections.*.questions.*.key' => ['required', 'alpha_dash'],
            'sections.*.questions.*.label' => ['required', 'string', 'max:255'], 'sections.*.questions.*.help_text' => ['nullable', 'string', 'max:4000'],
            'sections.*.questions.*.type' => ['required', 'in:'.implode(',', self::TYPES)], 'sections.*.questions.*.is_required' => ['boolean'],
            'sections.*.questions.*.options' => ['array'], 'sections.*.questions.*.options.*.key' => ['required', 'alpha_dash'],
            'sections.*.questions.*.options.*.label' => ['required', 'string', 'max:255'],
            'requirements' => ['required', 'array'], 'requirements.*.key' => ['required', 'alpha_dash', 'distinct'],
            'requirements.*.label' => ['required', 'string', 'max:255'], 'requirements.*.instructions' => ['nullable', 'string', 'max:4000'],
            'requirements.*.is_required' => ['boolean'], 'requirements.*.minimum_evidence' => ['required', 'integer', 'min:0'],
            'requirements.*.maximum_evidence' => ['required', 'integer', 'min:1'], 'requirements.*.provider_ids' => ['required', 'array', 'min:1'],
            'requirements.*.provider_ids.*' => ['integer', 'exists:evidence_provider_types,id'],
        ])->after(function ($validator) use ($state): void {
            $keys = collect($state['sections'] ?? [])->flatMap(fn ($section) => collect($section['questions'] ?? [])->pluck('key'));
            if ($keys->duplicates()->isNotEmpty()) {
                $validator->errors()->add('sections', 'Every question needs a unique internal identifier.');
            }
            foreach ($state['sections'] ?? [] as $si => $section) {
                foreach ($section['questions'] ?? [] as $qi => $question) {
                    if (in_array($question['type'] ?? null, ['single_choice', 'multiple_choice'], true) && count($question['options'] ?? []) < 2) {
                        $validator->errors()->add("sections.$si.questions.$qi.options", 'Add at least two answer choices.');
                    }
                }
            }
            foreach ($state['requirements'] ?? [] as $ri => $requirement) {
                if (($requirement['maximum_evidence'] ?? 0) < ($requirement['minimum_evidence'] ?? 0)) {
                    $validator->errors()->add("requirements.$ri.maximum_evidence", 'Maximum links must be at least the minimum.');
                }
            }
        })->validate();
    }
}
