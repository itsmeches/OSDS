<?php

namespace App\Filament\Forms;

use App\Models\FormQuestion;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class ConditionRuleField
{
    public static function make(string $name = 'condition_rules'): Repeater
    {
        return Repeater::make($name)
            ->label('Conditional applicability')
            ->helperText('Add one group. Every referenced question must appear earlier in the form; publication performs full validation.')
            ->maxItems(1)
            ->itemLabel('Condition group')
            ->schema([
                Select::make('mode')->options(['all' => 'All conditions must match', 'any' => 'Any condition may match'])->required()->default('all'),
                Repeater::make('conditions')->minItems(1)->schema([
                    TextInput::make('question')->label('Earlier question key')->required()->datalist(fn () => FormQuestion::query()->orderBy('key')->distinct()->pluck('key')->all()),
                    Select::make('operator')->required()->live()->options([
                        'equals' => 'Equals', 'not_equals' => 'Does not equal', 'in' => 'Is one of', 'not_in' => 'Is not one of',
                        'is_empty' => 'Is empty', 'is_not_empty' => 'Is not empty',
                    ]),
                    TextInput::make('value')->visible(fn (Get $get) => in_array($get('operator'), ['equals', 'not_equals'], true))->required(fn (Get $get) => in_array($get('operator'), ['equals', 'not_equals'], true)),
                    TagsInput::make('values')->visible(fn (Get $get) => in_array($get('operator'), ['in', 'not_in'], true))->required(fn (Get $get) => in_array($get('operator'), ['in', 'not_in'], true)),
                ])->columns(2),
            ])
            ->afterStateHydrated(function (Repeater $component, mixed $state): void {
                if (! is_array($state) || $state === []) {
                    return;
                }
                $mode = array_key_exists('any', $state) ? 'any' : 'all';
                $conditions = $state[$mode] ?? [];
                foreach ($conditions as &$condition) {
                    if (in_array($condition['operator'] ?? null, ['in', 'not_in'], true)) {
                        $condition['values'] = $condition['value'] ?? [];
                        unset($condition['value']);
                    }
                }
                $component->state([['mode' => $mode, 'conditions' => $conditions]]);
            })
            ->dehydrateStateUsing(function (mixed $state): ?array {
                if (! is_array($state) || $state === []) {
                    return null;
                }
                $group = array_values($state)[0];
                $mode = in_array($group['mode'] ?? null, ['all', 'any'], true) ? $group['mode'] : 'all';
                $conditions = $group['conditions'] ?? [];
                foreach ($conditions as &$condition) {
                    unset($condition['mode']);
                    if (in_array($condition['operator'] ?? null, ['in', 'not_in'], true)) {
                        $condition['value'] = array_values($condition['values'] ?? []);
                    }
                    unset($condition['values']);
                    if (in_array($condition['operator'] ?? null, ['is_empty', 'is_not_empty'], true)) {
                        unset($condition['value']);
                    }
                }

                return [$mode => array_values($conditions)];
            })
            ->columnSpanFull();
    }
}
