<?php

namespace App\Services\Questionnaires;

use App\Models\FormVersion;
use App\Models\RequirementSetVersion;

class ConfigurationHasher
{
    public function form(FormVersion $version): string
    {
        $version->loadMissing('definition.module', 'sections.questions.options');
        $payload = [
            'module' => $version->definition->module->code,
            'definition' => $version->definition->key,
            'name' => $version->definition->name,
            'audience' => $version->definition->audience,
            'version' => $version->version_number,
            'sections' => $version->sections->sortBy('sort_order')->values()->map(fn ($section) => [
                'key' => $section->key, 'title' => $section->title, 'description' => $section->description, 'order' => $section->sort_order,
                'questions' => $section->questions->sortBy('sort_order')->values()->map(fn ($question) => [
                    'key' => $question->key, 'type' => $question->type, 'label' => $question->label, 'help' => $question->help_text,
                    'required' => $question->is_required, 'order' => $question->sort_order,
                    'validation' => $this->canonical($question->validation_rules ?? []), 'condition' => $this->canonical($question->condition_rules),
                    'options' => $question->options->sortBy('sort_order')->values()->map(fn ($option) => ['key' => $option->key, 'label' => $option->label, 'order' => $option->sort_order])->all(),
                ])->all(),
            ])->all(),
        ];

        return hash('sha256', json_encode($this->canonical($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function requirements(RequirementSetVersion $version): string
    {
        $version->loadMissing('module', 'requirements.providers');
        $payload = [
            'module' => $version->module->code, 'version' => $version->version_number,
            'requirements' => $version->requirements->sortBy('sort_order')->values()->map(fn ($requirement) => [
                'key' => $requirement->key, 'label' => $requirement->label, 'instructions' => $requirement->instructions,
                'required' => $requirement->is_required, 'minimum' => $requirement->minimum_evidence,
                'maximum' => $requirement->maximum_evidence, 'order' => $requirement->sort_order,
                'condition' => $this->canonical($requirement->condition_rules),
                'providers' => $requirement->providers->pluck('code')->sort()->values()->all(),
            ])->all(),
        ];

        return hash('sha256', json_encode($this->canonical($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function combined(string $formHash, string $requirementHash): string
    {
        return hash('sha256', $formHash.':'.$requirementHash);
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonical($item), $value);
        }
        ksort($value);

        return array_map(fn ($item) => $this->canonical($item), $value);
    }
}
