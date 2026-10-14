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
        $form->loadMissing('definition', 'sections.questions.options');
        $requirements->loadMissing('requirements.providers');
        if ($form->definition->module_id !== $requirements->module_id) {
            $this->invalid('The form and requirement set must belong to the same module.');
        }
        if ($form->sections->isEmpty()) {
            $this->invalid('At least one section is required.');
        }

        $available = [];
        foreach ($form->sections->sortBy('sort_order') as $section) {
            if ($section->questions->isEmpty()) {
                $this->invalid("Section {$section->key} must contain a question.");
            }
            foreach ($section->questions->sortBy('sort_order') as $question) {
                if ($question->form_version_id !== $form->id) {
                    $this->invalid('A question belongs to a different form version.');
                }
                $this->conditions->validate($question->condition_rules, $available);
                $options = $question->options->pluck('key')->all();
                if (in_array($question->type, ['single_choice', 'multiple_choice'], true) && $options === []) {
                    $this->invalid("Question {$question->key} requires choices.");
                }
                $available[$question->key] = ['type' => $question->type, 'options' => $options];
            }
        }
        foreach ($requirements->requirements as $requirement) {
            $this->conditions->validate($requirement->condition_rules, $available);
            if ($requirement->providers->where('is_enabled', true)->isEmpty()) {
                $this->invalid("Requirement {$requirement->key} requires an enabled evidence provider.");
            }
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['configuration' => $message]);
    }
}
