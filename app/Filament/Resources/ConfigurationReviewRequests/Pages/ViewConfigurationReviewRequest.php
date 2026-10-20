<?php

namespace App\Filament\Resources\ConfigurationReviewRequests\Pages;

use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\PublicationManifests\PublicationManifestResource;
use App\Models\ConfigurationReviewRequest;
use App\Models\PublicationManifest;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\StaffActionFeedback;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewConfigurationReviewRequest extends ViewRecord
{
    protected static string $resource = ConfigurationReviewRequestResource::class;

    protected string $view = 'filament.resources.configuration-review-requests.view';

    public function getTitle(): string
    {
        return 'Review '.$this->getRecord()->module->name;
    }

    public function getSubheading(): string
    {
        return 'Inspect the complete questionnaire and document requirements before recording a decision.';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->getRecord()->load([
            'module', 'submittedBy', 'approvals', 'formVersion.definition',
            'formVersion.sections.questions.options', 'requirementSetVersion.requirements.providers',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')->extraAttributes(['wire:cloak' => true])
                ->label('Approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->state === 'pending' && auth()->user()?->can('questionnaires.review'))
                ->schema([
                    TextInput::make('reference')->label('Approval reference')->helperText('Use a synthetic review reference for this staff-only sandbox.')->required(),
                    Textarea::make('comment')->label('Reviewer notes'),
                ])
                ->modalHeading('Approve this exact configuration?')
                ->modalDescription('Approval records the displayed questionnaire version, document-requirements version, and configuration hash. Publishing remains a separate action.')
                ->modalSubmitActionLabel('Approve configuration')
                ->modalContent(view('filament.partials.staff-action-recovery-notice'))->requiresConfirmation()
                ->action(function (array $data): void {
                    if (! StaffActionFeedback::run(fn () => app(QuestionnairePublicationService::class)->review(auth()->user()->fresh(), $this->getRecord(), true, $data['reference'], $data['comment'] ?? null))) {
                        return;
                    }
                    Notification::make()->title('Exact configuration approved')->body('Choose the staff preview or synthetic applicant audience when ready. Approval alone does not make it available to applicants.')->success()->send();
                    $this->redirect(ConfigurationReviewRequestResource::getUrl('view', ['record' => $this->getRecord()]));
                }),
            Action::make('reject')->extraAttributes(['wire:cloak' => true])
                ->label('Request changes')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->state === 'pending' && auth()->user()?->can('questionnaires.review'))
                ->schema([
                    TextInput::make('reference')->label('Review reference')->required(),
                    Textarea::make('comment')->label('Required changes')->helperText('Explain what OSDS needs to correct before submitting again.')->required(),
                ])
                ->modalHeading('Return this configuration for changes?')
                ->modalDescription('The exact review remains in history. Its questionnaire and document requirements will return to editable draft state.')
                ->modalSubmitActionLabel('Request changes')
                ->modalContent(view('filament.partials.staff-action-recovery-notice'))->requiresConfirmation()
                ->action(function (array $data): void {
                    if (! StaffActionFeedback::run(fn () => app(QuestionnairePublicationService::class)->review(auth()->user()->fresh(), $this->getRecord(), false, $data['reference'], $data['comment']))) {
                        return;
                    }
                    Notification::make()->title('Configuration returned for changes')->warning()->send();
                    $this->redirect(ConfigurationReviewRequestResource::getUrl());
                }),
            Action::make('publish')->extraAttributes(['wire:cloak' => true])
                ->label('Publish staff preview')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->visible(fn (): bool => $this->getRecord()->state === 'approved'
                    && auth()->user()?->can('questionnaires.publish.sandbox')
                    && ! $this->hasPublication('review_sandbox'))
                ->schema([
                    DateTimePicker::make('effective_from')->label('Available from')->default(now())->required(),
                    DateTimePicker::make('effective_until')->label('Available until')->helperText('Optional. Leave blank when no review end date is needed.')->after('effective_from'),
                ])
                ->modalHeading('Publish to the staff review sandbox?')
                ->modalDescription('This creates an immutable staff-only preview. It does not create a live applicant form and saves no applicant responses.')
                ->modalContent(view('filament.partials.staff-action-recovery-notice'))->modalSubmitActionLabel('Publish to review sandbox')
                ->action(function (array $data): void {
                    if (StaffActionFeedback::run(function () use ($data): void {
                        $approval = $this->getRecord()->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                        app(QuestionnairePublicationService::class)->publishSandbox(auth()->user()->fresh(), $approval, $data['effective_from'], $data['effective_until'] ?? null);
                    })) {
                        Notification::make()->title('Published to the staff preview')->body('The staff-only preview is saved. Applicant access requires its separate publication and eligibility.')->success()->send();
                        $this->returnToSavedPublication();
                    }
                }),
            Action::make('publishApplicantSandbox')->extraAttributes(['wire:cloak' => true])
                ->label('Publish to applicant sandbox')
                ->icon(Heroicon::OutlinedUserGroup)
                ->visible(fn (): bool => $this->getRecord()->state === 'approved' && auth()->user()?->can('applicant.sandbox.publish') && ! $this->hasPublication('applicant_sandbox', activeOnly: true))
                ->schema([
                    DateTimePicker::make('effective_from')->label('Available from')->default(now())->required(),
                    DateTimePicker::make('effective_until')->label('Available until')->helperText('Optional. Applicant access ends at this time.')->after('effective_from'),
                ])
                ->modalHeading('Publish to the isolated applicant sandbox?')
                ->modalDescription('This is a separate synthetic SCHOOL audience. The staff review publication is not exposed.')->modalContent(view('filament.partials.staff-action-recovery-notice'))
                ->modalSubmitActionLabel('Publish to applicant sandbox')
                ->action(function (array $data): void {
                    if (StaffActionFeedback::run(function () use ($data): void {
                        $approval = $this->getRecord()->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                        app(QuestionnairePublicationService::class)->publishApplicantSandbox(auth()->user()->fresh(), $approval, $data['effective_from'], $data['effective_until'] ?? null);
                    })) {
                        Notification::make()->title('Published to applicant sandbox')->body('Eligible synthetic organizations can now select it when its availability period starts.')->success()->send();
                        $this->returnToSavedPublication();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        /** @var ConfigurationReviewRequest $record */
        $record = $this->getRecord();

        return [
            'review' => $record,
            'conditionSections' => $record->formVersion->sections->map(fn ($section): array => [
                'questions' => $section->questions->map(fn ($question): array => [
                    'key' => $question->key, 'label' => $question->label,
                    'options' => $question->options->map(fn ($option): array => ['key' => $option->key, 'label' => $option->label])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    private function hasPublication(string $channel, bool $activeOnly = false): bool
    {
        $approvalIds = $this->getRecord()->approvals()->where('outcome', 'approved')->pluck('id');

        return PublicationManifest::query()
            ->whereIn('configuration_review_approval_id', $approvalIds)
            ->where('channel', $channel)
            ->when($activeOnly, fn ($query) => $query->whereDoesntHave('retirement'))
            ->exists();
    }

    private function returnToSavedPublication(): void
    {
        $this->redirect(auth()->user()->fresh()->can('questionnaires.publication.view')
            ? PublicationManifestResource::getUrl()
            : ConfigurationReviewRequestResource::getUrl('view', ['record' => $this->getRecord()]));
    }
}
