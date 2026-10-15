<?php

namespace App\Filament\Pages;

use App\Models\EvidenceProviderType;
use App\Models\FormVersion;
use App\Models\RequirementSetVersion;
use App\Services\Questionnaires\ConditionalLogic;
use App\Services\Questionnaires\ConfigurationValidator;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\FoundationPermission;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormBuilder extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'form-builder/{form}/{requirements}';

    protected static ?string $title = 'Form Builder';

    protected string $view = 'filament.pages.form-builder';

    public FormVersion $formVersion;

    public RequirementSetVersion $requirementVersion;

    public array $sections = [];

    public array $documentRequirements = [];

    public array $previewAnswers = [];

    public array $findings = [];

    /** @var array<int, array{field: string, message: string, anchor: string}> */
    public array $saveErrors = [];

    public int $formLock;

    public int $requirementLock;

    public string $saveState = 'Saved';

    public string $mode = 'edit_preview';

    public string $stage = 'build';

    public ?string $selectedQuestionKey = null;

    public ?string $selectedRequirementKey = null;

    public bool $previewOpen = false;

    public bool $previewTesting = false;

    public bool $previewFullWidth = false;

    public string $formTitle = '';

    public bool $ready = false;

    public function mount(int|string $form, int|string $requirements): void
    {
        abort_unless(static::canAccess(), 403);
        $this->formVersion = FormVersion::query()->with('definition.module', 'sections.questions.options')->findOrFail($form);
        $this->requirementVersion = RequirementSetVersion::query()->with('requirements.providers')->findOrFail($requirements);
        abort_unless($this->formVersion->definition->module_id === $this->requirementVersion->module_id, 404);
        $this->formLock = $this->formVersion->lock_version;
        $this->requirementLock = $this->requirementVersion->lock_version;
        $this->formTitle = $this->formVersion->definition->name;
        $this->loadWorkingState();
        $this->selectedQuestionKey = $this->sections[0]['questions'][0]['key'] ?? null;
        $this->selectedRequirementKey = $this->documentRequirements[0]['key'] ?? null;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isActive()
            && auth()->user()?->can(FoundationPermission::ManageQuestionnaireDrafts->value);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'previewAnswers') || in_array($property, [
            'mode', 'saveState', 'stage', 'selectedQuestionKey', 'selectedRequirementKey',
            'previewOpen', 'previewTesting', 'previewFullWidth',
        ], true)) {
            return;
        }
        $this->saveState = 'Unsaved changes';
        $this->ready = false;
        $this->findings = [];
        $this->saveErrors = [];
    }

    public function addSection(): void
    {
        $this->sections[] = ['key' => $this->uniqueKey('section'), 'title' => 'New section', 'description' => '', 'questions' => []];
        $this->markDirty();
    }

    public function addQuestion(int $section): void
    {
        $key = $this->uniqueKey('question');
        $this->sections[$section]['questions'][] = [
            'key' => $key, 'label' => 'Untitled question', 'help_text' => '', 'type' => 'short_text',
            'is_required' => false, 'validation_rules' => [], 'condition_rules' => null, 'condition_source' => '',
            'condition_operator' => 'equals', 'condition_value' => '', 'options' => [],
        ];
        $this->selectedQuestionKey = $key;
        $this->markDirty();
    }

    public function duplicateQuestion(int $section, int $question): void
    {
        $copy = $this->sections[$section]['questions'][$question];
        $copy['key'] = $this->uniqueKey(Str::slug($copy['label'], '_') ?: 'question');
        $copy['label'] .= ' copy';
        $copy['condition_rules'] = null;
        $copy['condition_source'] = '';
        foreach ($copy['options'] as &$option) {
            $option['key'] = $this->uniqueOptionKey($copy['options'], $option['label']);
        }
        array_splice($this->sections[$section]['questions'], $question + 1, 0, [$copy]);
        $this->selectedQuestionKey = $copy['key'];
        $this->markDirty();
    }

    public function removeQuestion(int $section, int $question): void
    {
        $removedKey = $this->sections[$section]['questions'][$question]['key'] ?? null;
        array_splice($this->sections[$section]['questions'], $question, 1);
        if ($this->selectedQuestionKey === $removedKey) {
            $this->selectedQuestionKey = $this->sections[$section]['questions'][$question]['key']
                ?? $this->sections[$section]['questions'][$question - 1]['key']
                ?? $this->firstQuestionKey();
        }
        $this->markDirty();
    }

    public function moveQuestion(int $section, int $question, int $direction): void
    {
        $target = $question + $direction;
        if (! isset($this->sections[$section]['questions'][$target])) {
            return;
        }
        $item = $this->sections[$section]['questions'][$question];
        array_splice($this->sections[$section]['questions'], $question, 1);
        array_splice($this->sections[$section]['questions'], $target, 0, [$item]);
        $this->markDirty();
    }

    public function addOption(int $section, int $question): void
    {
        $options = $this->sections[$section]['questions'][$question]['options'];
        $this->sections[$section]['questions'][$question]['options'][] = ['key' => $this->uniqueOptionKey($options, 'option'), 'label' => 'New option'];
        $this->markDirty();
    }

    public function removeOption(int $section, int $question, int $option): void
    {
        array_splice($this->sections[$section]['questions'][$question]['options'], $option, 1);
        $this->markDirty();
    }

    public function addRequirement(): void
    {
        $provider = EvidenceProviderType::query()->where('is_enabled', true)->value('id');
        $key = $this->uniqueKey('document');
        $this->documentRequirements[] = [
            'key' => $key, 'label' => 'Untitled document', 'instructions' => '', 'is_required' => true,
            'minimum_evidence' => 1, 'maximum_evidence' => 1, 'provider_ids' => $provider ? [$provider] : [],
            'condition_rules' => null, 'condition_source' => '', 'condition_operator' => 'equals', 'condition_value' => '',
        ];
        $this->selectedRequirementKey = $key;
        $this->markDirty();
    }

    public function removeRequirement(int $index): void
    {
        $removedKey = $this->documentRequirements[$index]['key'] ?? null;
        array_splice($this->documentRequirements, $index, 1);
        if ($this->selectedRequirementKey === $removedKey) {
            $this->selectedRequirementKey = $this->documentRequirements[$index]['key']
                ?? $this->documentRequirements[$index - 1]['key']
                ?? ($this->documentRequirements[0]['key'] ?? null);
        }
        $this->markDirty();
    }

    public function moveRequirement(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->documentRequirements[$target])) {
            return;
        }

        $item = $this->documentRequirements[$index];
        array_splice($this->documentRequirements, $index, 1);
        array_splice($this->documentRequirements, $target, 0, [$item]);
        $this->selectedRequirementKey = $item['key'];
        $this->markDirty();
    }

    public function selectQuestion(string $key): void
    {
        if ($this->questionSelection($key) !== null) {
            $this->selectedQuestionKey = $key;
        }
    }

    public function selectRequirement(string $key): void
    {
        if (collect($this->documentRequirements)->contains('key', $key)) {
            $this->selectedRequirementKey = $key;
        }
    }

    public function showStage(string $stage): void
    {
        if (in_array($stage, ['build', 'documents', 'review', 'send'], true)) {
            $this->stage = $stage;
        }
    }

    public function togglePreview(bool $fullWidth = false): void
    {
        $this->previewOpen = ! $this->previewOpen || $this->previewFullWidth !== $fullWidth;
        $this->previewFullWidth = $this->previewOpen && $fullWidth;
    }

    /** @return array{section: int, question: int, data: array}|null */
    public function selectedQuestionSelection(): ?array
    {
        return $this->selectedQuestionKey ? $this->questionSelection($this->selectedQuestionKey) : null;
    }

    /** @return array{requirement: int, data: array}|null */
    public function selectedRequirementSelection(): ?array
    {
        foreach ($this->documentRequirements as $index => $requirement) {
            if ($requirement['key'] === $this->selectedRequirementKey) {
                return ['requirement' => $index, 'data' => $requirement];
            }
        }

        return null;
    }

    public function questionHasError(int $section, int $question): bool
    {
        $prefix = "sections.$section.questions.$question";

        return collect($this->saveErrors)->contains(fn (array $error): bool => str_starts_with($error['field'], $prefix));
    }

    public function requirementHasError(int $requirement): bool
    {
        $prefix = "documentRequirements.$requirement";

        return collect($this->saveErrors)->contains(fn (array $error): bool => str_starts_with($error['field'], $prefix));
    }

    public function save(QuestionnaireAuthoringService $service): void
    {
        $this->saveState = 'Saving';
        $this->resetErrorBag();
        $this->saveErrors = [];
        try {
            $this->compileConditions();
            [$this->formLock, $this->requirementLock] = $service->save(
                auth()->user(), $this->formVersion, $this->requirementVersion,
                ['title' => $this->formTitle, 'sections' => $this->sections, 'requirements' => $this->documentRequirements],
                $this->formLock, $this->requirementLock,
            );
            $this->saveState = 'Saved';
            $this->ready = false;
            $this->reloadModels();
            Notification::make()->title('Draft saved')->success()->send();
        } catch (ValidationException $e) {
            $this->saveState = $e->errors()['conflict'] ?? false ? 'Conflict' : 'Save failed';
            foreach ($e->errors() as $key => $messages) {
                $field = $this->builderField($key);
                $message = $messages[0];
                $this->addError($field, $message);
                $this->saveErrors[] = [
                    'field' => $field,
                    'message' => $message,
                    'anchor' => $this->builderErrorAnchor($field),
                ];
            }

            if ($this->saveState !== 'Conflict') {
                Notification::make()
                    ->title('Unable to save questionnaire')
                    ->body('Correct the highlighted fields. Your unsaved changes are still available on this page.')
                    ->warning()
                    ->persistent()
                    ->send();
            }
        }
    }

    public function check(ConfigurationValidator $validator): void
    {
        if ($this->saveState !== 'Saved') {
            $this->findings = [['message' => 'Save your changes before checking the form.', 'anchor' => 'builder-top']];
            $this->ready = false;

            return;
        }
        $findings = $validator->findings($this->formVersion->fresh(), $this->requirementVersion->fresh());
        if ($findings === []) {
            $this->findings = [];
            $this->ready = true;
            Notification::make()->title('Ready for review')->body('The latest saved configuration passed the authoritative check.')->success()->send();
        } else {
            $this->findings = $findings;
            $this->ready = false;
        }
    }

    public function submitForReview(
        QuestionnairePublicationService $service,
        ConfigurationValidator $validator,
    ): void {
        abort_unless(auth()->user()->can(FoundationPermission::SubmitQuestionnaireReview->value), 403);
        $this->resetErrorBag('review');

        if ($this->saveState !== 'Saved') {
            $this->addError('review', 'Save the draft before sending it for Admin approval.');

            return;
        }

        $form = $this->formVersion->fresh();
        $requirements = $this->requirementVersion->fresh();
        $this->findings = $validator->findings($form, $requirements);
        $this->ready = $this->findings === [];

        if (! $this->ready) {
            $this->stage = 'review';
            $this->addError('review', 'The saved configuration needs attention before it can be sent. Review the listed issues.');
            Notification::make()
                ->title('Configuration needs attention')
                ->body('Review the listed issues, save your corrections, and try again.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service->submitForReview(auth()->user(), $form, $requirements);
            Notification::make()->title('Sent for Admin review')->body('The exact saved questionnaire and document versions are now awaiting an Admin decision.')->success()->send();
            $this->redirect(Questionnaires::getUrl(['module' => $this->formVersion->definition->module->code]));
        } catch (ValidationException $e) {
            $this->ready = false;
            $this->addError('review', collect($e->errors())->flatten()->first() ?? 'The configuration could not be sent for review.');
            Notification::make()->title('Unable to send for review')->body('Your draft remains saved. Review the message on this page and try again.')->warning()->persistent()->send();
        }
    }

    public function visible(array $question): bool
    {
        return app(ConditionalLogic::class)->evaluate($this->compiledCondition($question), $this->previewAnswers);
    }

    public function visibleRequirement(array $requirement): bool
    {
        return app(ConditionalLogic::class)->evaluate($this->compiledCondition($requirement), $this->previewAnswers);
    }

    public function sourceOptions(string $key): array
    {
        foreach ($this->sections as $section) {
            foreach ($section['questions'] as $question) {
                if ($question['key'] === $key && in_array($question['type'], ['single_choice', 'multiple_choice'], true)) {
                    return collect($question['options'])->pluck('label', 'key')->all();
                }
            }
        }

        return [];
    }

    public function priorQuestions(int $sectionIndex, int $questionIndex): array
    {
        $result = [];
        foreach ($this->sections as $si => $section) {
            foreach ($section['questions'] as $qi => $question) {
                if ($si > $sectionIndex || ($si === $sectionIndex && $qi >= $questionIndex)) {
                    return $result;
                }
                $result[$question['key']] = $question['label'];
            }
        }

        return $result;
    }

    protected function getViewData(): array
    {
        return [
            'questionTypes' => ['short_text' => 'Short answer', 'long_text' => 'Paragraph', 'single_choice' => 'Multiple choice', 'multiple_choice' => 'Checkboxes', 'yes_no' => 'Yes or no', 'date' => 'Date', 'integer' => 'Whole number', 'decimal' => 'Decimal number', 'telephone' => 'Telephone', 'email' => 'Email', 'declaration' => 'Declaration'],
            'providers' => EvidenceProviderType::query()->where('is_enabled', true)->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function loadWorkingState(): void
    {
        $this->sections = $this->formVersion->sections->map(fn ($section) => [
            'key' => $section->key, 'title' => $section->title, 'description' => $section->description ?? '',
            'questions' => $section->questions->map(function ($question) {
                $condition = $this->conditionFields($question->condition_rules);

                return array_merge([
                    'key' => $question->key, 'label' => $question->label, 'help_text' => $question->help_text ?? '', 'type' => $question->type,
                    'is_required' => $question->is_required, 'validation_rules' => $question->validation_rules ?? [], 'condition_rules' => $question->condition_rules,
                    'options' => $question->options->map(fn ($option) => ['key' => $option->key, 'label' => $option->label])->all(),
                ], $condition);
            })->all(),
        ])->all();
        $this->documentRequirements = $this->requirementVersion->requirements->map(function ($requirement) {
            return array_merge([
                'key' => $requirement->key, 'label' => $requirement->label, 'instructions' => $requirement->instructions ?? '',
                'is_required' => $requirement->is_required, 'minimum_evidence' => $requirement->minimum_evidence,
                'maximum_evidence' => $requirement->maximum_evidence, 'provider_ids' => $requirement->providers->pluck('id')->all(),
                'condition_rules' => $requirement->condition_rules,
            ], $this->conditionFields($requirement->condition_rules));
        })->all();
    }

    private function conditionFields(?array $rules): array
    {
        $leaf = $rules['all'][0] ?? $rules['any'][0] ?? null;

        return ['condition_source' => $leaf['question'] ?? '', 'condition_operator' => $leaf['operator'] ?? 'equals', 'condition_value' => $leaf['value'] ?? ''];
    }

    private function compileConditions(): void
    {
        foreach ($this->sections as &$section) {
            foreach ($section['questions'] as &$question) {
                $question['condition_rules'] = $this->compiledCondition($question);
            }
        }
        foreach ($this->documentRequirements as &$requirement) {
            $requirement['condition_rules'] = $this->compiledCondition($requirement);
        }
    }

    private function compiledCondition(array $item): ?array
    {
        if (blank($item['condition_source'] ?? null)) {
            return null;
        }
        $leaf = ['question' => $item['condition_source'], 'operator' => $item['condition_operator'] ?: 'equals'];
        if (! in_array($leaf['operator'], ['is_empty', 'is_not_empty'], true)) {
            $leaf['value'] = $item['condition_value'] ?? '';
        }

        return ['all' => [$leaf]];
    }

    private function reloadModels(): void
    {
        $this->formVersion = $this->formVersion->fresh(['definition.module', 'sections.questions.options']);
        $this->requirementVersion = $this->requirementVersion->fresh(['requirements.providers']);
    }

    private function markDirty(): void
    {
        $this->saveState = 'Unsaved changes';
        $this->ready = false;
        $this->saveErrors = [];
    }

    private function builderField(string $field): string
    {
        if ($field === 'title') {
            return 'formTitle';
        }

        if ($field === 'requirements') {
            return 'documentRequirements';
        }

        if (str_starts_with($field, 'requirements.')) {
            return 'documentRequirements.'.substr($field, strlen('requirements.'));
        }

        return $field;
    }

    private function builderErrorAnchor(string $field): string
    {
        if (preg_match('/^sections\.(\d+)\.questions\.(\d+)/', $field, $matches) === 1) {
            return 'question-'.($this->sections[(int) $matches[1]]['questions'][(int) $matches[2]]['key'] ?? 'build-form');
        }

        if (str_starts_with($field, 'documentRequirements')) {
            return 'required-documents';
        }

        return 'build-form';
    }

    private function uniqueKey(string $base): string
    {
        $existing = collect($this->sections)->flatMap(fn ($section) => collect($section['questions'] ?? [])->pluck('key'))
            ->merge(collect($this->sections)->pluck('key'))->merge(collect($this->documentRequirements)->pluck('key'));
        $base = Str::slug($base, '_') ?: 'item';
        do {
            $key = $base.'_'.Str::lower(Str::random(6));
        } while ($existing->contains($key));

        return $key;
    }

    private function uniqueOptionKey(array $options, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'option';
        $existing = collect($options)->pluck('key');
        $key = $base;
        $suffix = 2;
        while ($existing->contains($key)) {
            $key = $base.'_'.$suffix++;
        }

        return $key;
    }

    /** @return array{section: int, question: int, data: array}|null */
    private function questionSelection(string $key): ?array
    {
        foreach ($this->sections as $sectionIndex => $section) {
            foreach ($section['questions'] as $questionIndex => $question) {
                if ($question['key'] === $key) {
                    return ['section' => $sectionIndex, 'question' => $questionIndex, 'data' => $question];
                }
            }
        }

        return null;
    }

    private function firstQuestionKey(): ?string
    {
        foreach ($this->sections as $section) {
            if (isset($section['questions'][0]['key'])) {
                return $section['questions'][0]['key'];
            }
        }

        return null;
    }
}
