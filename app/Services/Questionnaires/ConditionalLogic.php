<?php

namespace App\Services\Questionnaires;

use Illuminate\Validation\ValidationException;

class ConditionalLogic
{
    private const OPERATORS = ['equals', 'not_equals', 'in', 'not_in', 'is_empty', 'is_not_empty'];

    public function validate(?array $rules, array $availableQuestions): void
    {
        if ($rules === null || $rules === []) {
            return;
        }
        $this->validateNode($rules, $availableQuestions);
    }

    public function evaluate(?array $rules, array $answers): bool
    {
        if ($rules === null || $rules === []) {
            return true;
        }
        if (isset($rules['all'])) {
            return collect($rules['all'])->every(fn (array $node) => $this->evaluate($node, $answers));
        }
        if (isset($rules['any'])) {
            return collect($rules['any'])->contains(fn (array $node) => $this->evaluate($node, $answers));
        }

        $actual = $answers[$rules['question']] ?? null;

        return match ($rules['operator']) {
            'equals' => $actual === ($rules['value'] ?? null),
            'not_equals' => $actual !== ($rules['value'] ?? null),
            'in' => in_array($actual, $rules['value'] ?? [], true),
            'not_in' => ! in_array($actual, $rules['value'] ?? [], true),
            'is_empty' => $actual === null || $actual === '' || $actual === [],
            'is_not_empty' => ! ($actual === null || $actual === '' || $actual === []),
            default => false,
        };
    }

    private function validateNode(array $node, array $availableQuestions): void
    {
        $groups = array_values(array_intersect(['all', 'any'], array_keys($node)));
        if ($groups !== []) {
            if (count($groups) !== 1 || count($node) !== 1 || ! is_array($node[$groups[0]]) || $node[$groups[0]] === []) {
                $this->invalid('A condition group must contain a non-empty all or any list.');
            }
            foreach ($node[$groups[0]] as $child) {
                if (! is_array($child)) {
                    $this->invalid('Every grouped condition must be an object.');
                }
                $this->validateNode($child, $availableQuestions);
            }

            return;
        }

        $key = $node['question'] ?? null;
        $operator = $node['operator'] ?? null;
        if (! is_string($key) || ! array_key_exists($key, $availableQuestions)) {
            $this->invalid('A condition references a missing or later question.');
        }
        if (! in_array($operator, self::OPERATORS, true)) {
            $this->invalid('A condition uses an unsupported operator.');
        }
        if (in_array($operator, ['in', 'not_in'], true) && ! is_array($node['value'] ?? null)) {
            $this->invalid('The in operators require an array value.');
        }
        if (in_array($operator, ['is_empty', 'is_not_empty'], true) && array_key_exists('value', $node)) {
            $this->invalid('Empty operators do not accept a value.');
        }

        $question = $availableQuestions[$key];
        if (in_array($question['type'], ['single_choice', 'multiple_choice'], true) && array_key_exists('value', $node)) {
            $values = is_array($node['value']) ? $node['value'] : [$node['value']];
            if (array_diff($values, $question['options']) !== []) {
                $this->invalid('A condition references an invalid option key.');
            }
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['conditions' => $message]);
    }
}
