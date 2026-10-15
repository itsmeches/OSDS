<?php

namespace App\Filament\Pages;

use App\Filament\Resources\FormVersions\FormVersionResource;
use App\Filament\Resources\RequirementSetVersions\RequirementSetVersionResource;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\RequirementSetVersion;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\FoundationPermission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class Questionnaires extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Questionnaires';

    protected static ?string $title = 'Questionnaires';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.questionnaires';

    #[Url]
    public ?string $module = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isActive()
            && ($user->can(FoundationPermission::ManageQuestionnaireDrafts->value)
                || $user->can(FoundationPermission::SubmitQuestionnaireReview->value));
    }

    public function getSubheading(): string
    {
        return $this->selectedModule()
            ? 'Edit questions and document requirements, preview the combined draft, and submit the exact versions for review.'
            : 'Choose a program to manage its questionnaire and document requirements.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createQuestionnaire')
                ->label('Create questionnaire')
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => auth()->user()?->can(FoundationPermission::ManageQuestionnaireDrafts->value))
                ->schema([
                    Select::make('module_id')
                        ->label('Approved service')
                        ->options(fn (): array => OsdsModule::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => $this->selectedModule()?->id)
                        ->helperText('This uses an existing approved service. It does not create or authorize a new OSDS service.')
                        ->required(),
                    TextInput::make('title')->label('Questionnaire title')->required()->maxLength(255),
                ])
                ->modalDescription('Create the initial paired questionnaire and document-requirements draft under an existing approved service.')
                ->action(function (array $data): void {
                    $module = OsdsModule::query()->where('is_active', true)->findOrFail($data['module_id']);
                    [$form, $requirements] = app(QuestionnaireDraftService::class)->createPair(auth()->user(), $module, $data['title']);
                    $this->redirect(FormBuilder::getUrl(['form' => $form->id, 'requirements' => $requirements->id]));
                }),
            Action::make('openBuilder')
                ->label('Continue editing')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn (): bool => $this->hasDraftPair()
                    && auth()->user()?->can(FoundationPermission::ManageQuestionnaireDrafts->value))
                ->schema([
                    Select::make('form_version_id')->label('Questionnaire draft')
                        ->options(fn (): array => $this->formVersions('draft')->mapWithKeys(fn (FormVersion $version): array => [$version->id => "Questionnaire v{$version->version_number} — {$version->definition->name}"])->all())
                        ->default(fn (): ?int => $this->onlyDraftId($this->formVersions('draft')))->required(),
                    Select::make('requirement_set_version_id')->label('Document requirements draft')
                        ->options(fn (): array => $this->requirementVersions('draft')->mapWithKeys(fn (RequirementSetVersion $version): array => [$version->id => "Documents v{$version->version_number}"])->all())
                        ->default(fn (): ?int => $this->onlyDraftId($this->requirementVersions('draft')))->required(),
                ])
                ->modalDescription('Choose the exact draft pair to edit together. The builder will never replace either version silently.')
                ->action(fn (array $data) => $this->redirect(FormBuilder::getUrl(['form' => $data['form_version_id'], 'requirements' => $data['requirement_set_version_id']]))),
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(false)
                ->disabled(fn (): bool => ! $this->hasDraftPair())
                ->tooltip(fn (): ?string => $this->hasDraftPair()
                    ? null
                    : 'A questionnaire draft and a document-requirements draft are both required. This configuration may already be in review or published.')
                ->schema([
                    Select::make('form_version_id')
                        ->label('Questionnaire draft')
                        ->helperText(fn (): string => $this->formVersions('draft')->isEmpty()
                            ? 'No questionnaire draft is available. This version may already be in review or published.'
                            : 'Select the exact questionnaire version to review.')
                        ->options(fn (): array => $this->formVersions('draft')->mapWithKeys(fn (FormVersion $version): array => [$version->id => "Questionnaire v{$version->version_number} — {$version->definition->name}"])->all())
                        ->default(fn (): ?int => $this->onlyDraftId($this->formVersions('draft')))
                        ->required(),
                    Select::make('requirement_set_version_id')
                        ->label('Document requirements draft')
                        ->helperText(fn (): string => $this->requirementVersions('draft')->isEmpty()
                            ? 'No document-requirements draft is available. This version may already be in review or published.'
                            : 'Select the exact document-requirements version to pair with the questionnaire.')
                        ->options(fn (): array => $this->requirementVersions('draft')->mapWithKeys(fn (RequirementSetVersion $version): array => [$version->id => "Documents v{$version->version_number}"])->all())
                        ->default(fn (): ?int => $this->onlyDraftId($this->requirementVersions('draft')))
                        ->required(),
                ])
                ->modalHeading('Submit this configuration for review?')
                ->modalDescription('Both selected drafts will be frozen while an Admin reviews their exact content. You cannot edit them unless the review is rejected.')
                ->modalSubmitActionLabel('Submit for review')
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    app(QuestionnairePublicationService::class)->submitForReview(
                        auth()->user(),
                        FormVersion::query()->findOrFail($data['form_version_id']),
                        RequirementSetVersion::query()->findOrFail($data['requirement_set_version_id']),
                    );

                    Notification::make()
                        ->title('Configuration submitted for review')
                        ->body('What happens next? An authorized Admin will inspect the exact questionnaire and document requirements.')
                        ->success()
                        ->send();
                }),
            Action::make('createNewDraft')
                ->label('Create new draft')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->visible(fn (): bool => $this->selectedModule() !== null
                    && auth()->user()?->can(FoundationPermission::ManageQuestionnaireDrafts->value)
                    && ($this->formVersions()->whereIn('state', ['in_review', 'published', 'retired'])->isNotEmpty()))
                ->schema([
                    Select::make('form_version_id')
                        ->label('Copy questionnaire')
                        ->options(fn (): array => $this->formVersions()->mapWithKeys(fn (FormVersion $version): array => [$version->id => "Questionnaire v{$version->version_number} — ".str($version->state)->replace('_', ' ')->title()])->all())
                        ->required(),
                    Select::make('requirement_set_version_id')
                        ->label('Copy document requirements')
                        ->options(fn (): array => $this->requirementVersions()->mapWithKeys(fn (RequirementSetVersion $version): array => [$version->id => "Documents v{$version->version_number} — ".str($version->state)->replace('_', ' ')->title()])->all())
                        ->required(),
                ])
                ->modalDescription('Published and reviewed versions remain unchanged. The system will create new editable draft versions.')
                ->action(function (array $data): void {
                    $service = app(QuestionnaireDraftService::class);
                    $service->cloneForm(auth()->user(), FormVersion::query()->findOrFail($data['form_version_id']));
                    $service->cloneRequirements(auth()->user(), RequirementSetVersion::query()->findOrFail($data['requirement_set_version_id']));

                    Notification::make()->title('New draft versions created')->body('You can now edit the copied questionnaire and document requirements.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $selected = $this->selectedModule();

        return [
            'modules' => OsdsModule::query()->where('is_active', true)->orderBy('name')->get(),
            'moduleStatus' => fn (OsdsModule $module): string => $this->moduleStatus($module),
            'selectedModule' => $selected,
            'formVersions' => $selected ? $this->formVersions() : collect(),
            'requirementVersions' => $selected ? $this->requirementVersions() : collect(),
            'canSubmitDraftPair' => $selected ? $this->hasDraftPair() : false,
            'draftFormVersions' => $selected ? $this->formVersions('draft') : collect(),
            'draftRequirementVersions' => $selected ? $this->requirementVersions('draft') : collect(),
            'builderUrl' => $selected && $this->formVersions('draft')->count() === 1 && $this->requirementVersions('draft')->count() === 1
                ? FormBuilder::getUrl([
                    'form' => $this->formVersions('draft')->first()->id,
                    'requirements' => $this->requirementVersions('draft')->first()->id,
                ])
                : null,
            'editFormUrl' => fn (FormVersion $version): string => FormVersionResource::getUrl('edit', ['record' => $version]),
            'editRequirementsUrl' => fn (RequirementSetVersion $version): string => RequirementSetVersionResource::getUrl('edit', ['record' => $version]),
        ];
    }

    private function selectedModule(): ?OsdsModule
    {
        if (blank($this->module)) {
            return null;
        }

        return OsdsModule::query()->where('code', $this->module)->where('is_active', true)->first();
    }

    private function moduleStatus(OsdsModule $module): string
    {
        $states = FormVersion::query()
            ->whereHas('definition', fn ($query) => $query->where('module_id', $module->id))
            ->pluck('state');

        foreach (['in_review', 'draft', 'published', 'retired'] as $state) {
            if ($states->contains($state)) {
                return $state;
            }
        }

        return 'draft';
    }

    private function formVersions(?string $state = null)
    {
        $module = $this->selectedModule();
        if (! $module) {
            return collect();
        }

        return FormVersion::query()
            ->with(['definition', 'sections.questions.options'])
            ->whereHas('definition', fn ($query) => $query->where('module_id', $module->id))
            ->when($state, fn ($query) => $query->where('state', $state))
            ->orderByDesc('version_number')
            ->get();
    }

    private function requirementVersions(?string $state = null)
    {
        $module = $this->selectedModule();
        if (! $module) {
            return collect();
        }

        return RequirementSetVersion::query()
            ->with(['requirements.providers'])
            ->where('module_id', $module->id)
            ->when($state, fn ($query) => $query->where('state', $state))
            ->orderByDesc('version_number')
            ->get();
    }

    private function hasDraftPair(): bool
    {
        return $this->formVersions('draft')->isNotEmpty()
            && $this->requirementVersions('draft')->isNotEmpty();
    }

    private function onlyDraftId($versions): ?int
    {
        return $versions->count() === 1 ? $versions->first()->id : null;
    }
}
