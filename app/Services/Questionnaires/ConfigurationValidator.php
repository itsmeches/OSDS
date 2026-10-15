<?php

namespace App\Services\Questionnaires;

use App\Models\FormVersion;
use App\Models\RequirementSetVersion;
use Illuminate\Validation\ValidationException;

class ConfigurationValidator
{
    public function __construct(private readonly ConditionalLogic $conditions) {}

    public function validate(FormVersion $form, RequirementSetVersion $requirements): void
    {
        $findings = $this->findings($form, $requirements);
        if ($findings !== []) {
            throw ValidationException::withMessages(collect($findings)->mapWithKeys(fn (array $finding, int $index) => ["configuration.$index" => $finding['message']])->all());
        }
    }

    public function findings(FormVersion $form, RequirementSetVersion $requirements): array
    {
        $form->loadMissing('definition', 'sections.questions.options');
        $requirements->loadMissing('requirements.providers');
        $findings = [];
        $add = function (string $message, string $anchor = 'builder-editor') use (&$findings): void {
            $findings[] = ['message' => $message, 'anchor' => $anchor];
        };
        if ($form->definition->module_id !== $requirements->module_id) {
            $add('The form and document requirements must belong to the same program.');
        }
        if ($form->sections->isEmpty()) {
            $add('Add at least one section.', 'build-form');
        }

        $available = [];
        foreach ($form->sections->sortBy('sort_order') as $section) {
            if ($section->questions->isEmpty()) {
                $add("Add at least one question to {$section->title}.", 'build-form');
            }
            foreach ($section->questions->sortBy('sort_order') as $question) {
                if ($question->form_version_id !== $form->id) {
                    $add('A question belongs to a different questionnaire version.', 'question-'.$question->key);
                }
                if (blank($question->label)) {
                    $add('This question needs a title.', 'question-'.$question->key);
                }
                try {
                    $this->conditions->validate($question->condition_rules, $available);
                } catch (ValidationException $e) {
                    $add($question->label.': '.collect($e->errors())->flatten()->first(), 'question-'.$question->key);
                }
                $options = $question->options->pluck('key')->all();
                if (in_array($question->type, ['single_choice', 'multiple_choice'], true) && count($options) < 2) {
                    $add($question->label.': add at least two answer choices.', 'question-'.$question->key);
                }
                $available[$question->key] = ['type' => $question->type, 'options' => $options];
            }
        }
        foreach ($requirements->requirements as $requirement) {
            try {
                $this->conditions->validate($requirement->condition_rules, $available);
            } catch (ValidationException $e) {
                $add($requirement->label.': '.collect($e->errors())->flatten()->first(), 'required-documents');
            }
            if ($requirement->providers->where('is_enabled', true)->isEmpty()) {
                $add($requirement->label.': select at least one accepted link type.', 'required-documents');
            }
        }

        return $findings;
    }
}
