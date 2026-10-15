<?php

namespace App\Filament\Resources\ConfigurationReviewRequests\Pages;

use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\PublicationManifests\PublicationManifestResource;
use App\Models\ConfigurationReviewRequest;
use App\Models\PublicationManifest;
use App\Services\Questionnaires\QuestionnairePublicationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

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
            Action::make('approve')
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
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    app(QuestionnairePublicationService::class)->review(auth()->user(), $this->getRecord(), true, $data['reference'], $data['comment'] ?? null);
                    Notification::make()->title('Exact configuration approved')->body('What happens next? Publish it to the staff-only review sandbox when ready.')->success()->send();
                    $this->redirect(ConfigurationReviewRequestResource::getUrl('view', ['record' => $this->getRecord()]));
                }),
            Action::make('reject')
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
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    app(QuestionnairePublicationService::class)->review(auth()->user(), $this->getRecord(), false, $data['reference'], $data['comment']);
                    Notification::make()->title('Configuration returned for changes')->warning()->send();
                    $this->redirect(ConfigurationReviewRequestResource::getUrl());
                }),
            Action::make('publish')
                ->label('Publish to review sandbox')
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
                ->modalSubmitActionLabel('Publish to review sandbox')
                ->action(function (array $data): void {
                    try {
                        $approval = $this->getRecord()->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                        app(QuestionnairePublicationService::class)->publishSandbox(auth()->user(), $approval, $data['effective_from'], $data['effective_until'] ?? null);
                        Notification::make()->title('Published to the review sandbox')->body('Open Published questionnaires to preview it or review publication history.')->success()->send();
                        $this->redirect(PublicationManifestResource::getUrl());
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()->title('Publication failed')->body($exception->getMessage())->danger()->persistent()->send();
                    }
                }),
            Action::make('publishApplicantSandbox')
                ->label('Publish to applicant sandbox')
                ->icon(Heroicon::OutlinedUserGroup)
                ->visible(fn (): bool => $this->getRecord()->state === 'approved' && auth()->user()?->can('applicant.sandbox.publish'))
                ->schema([DateTimePicker::make('effective_from')->default(now())->required(), DateTimePicker::make('effective_until')->after('effective_from')])
                ->modalHeading('Publish to the isolated applicant sandbox?')
                ->modalDescription('This is a separate synthetic SCHOOL audience. The staff review publication is not exposed.')
                ->action(function (array $data): void {
                    try {
                        $approval = $this->getRecord()->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                        app(QuestionnairePublicationService::class)->publishApplicantSandbox(auth()->user(), $approval, $data['effective_from'], $data['effective_until'] ?? null);
                        Notification::make()->title('Published to applicant sandbox')->success()->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()->title('Applicant sandbox publication failed')->body($exception->getMessage())->danger()->persistent()->send();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        /** @var ConfigurationReviewRequest $record */
        $record = $this->getRecord();

        return ['review' => $record];
    }

    private function hasPublication(string $channel): bool
    {
        $approvalIds = $this->getRecord()->approvals()->where('outcome', 'approved')->pluck('id');

        return PublicationManifest::query()
            ->whereIn('configuration_review_approval_id', $approvalIds)
            ->where('channel', $channel)
            ->exists();
    }
}
