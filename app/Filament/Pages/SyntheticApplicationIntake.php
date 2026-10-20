<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ScopesReviewerQueue;
use App\Models\Application;
use App\Models\ApplicationSubmission;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Authorization\ReviewerQueueScope;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class SyntheticApplicationIntake extends Page
{
    use ScopesReviewerQueue;

    #[Url]
    public ?string $submission = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Application intake';

    protected static ?string $title = 'Application intake';

    protected static ?int $navigationSort = 14;

    protected string $view = 'filament.pages.synthetic-application-intake';

    #[Url]
    public ?string $application = null;

    public string $correctionSummary = '';

    public string $correctionTarget = 'general';

    public ?int $correctionQuestion = null;

    public ?int $correctionRequirement = null;

    public string $correctionCategory = 'clarification';

    public string $correctionInstruction = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('applications.intake.view');
    }

    protected function queueQuery(): Builder
    {
        return app(ReviewerQueueScope::class)->applications(
            Application::query()->with(['institution', 'manifest.module', 'module', 'region'])->whereNotNull('current_submission_id'),
            auth()->user(),
        );
    }

    #[Computed]
    public function applications(): LengthAwarePaginator
    {
        $query = $this->filterQueue($this->queueQuery(), 'routing_region_id');
        if ($this->status !== '') {
            $query->where('state', $this->status);
        }

        return $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(10, page: $this->getPage());
    }

    public function updatedCorrectionTarget(): void
    {
        $this->correctionQuestion = null;
        $this->correctionRequirement = null;
        $this->resetValidation(['items']);
    }

    #[Computed]
    public function selected(): ?Application
    {
        if (! $this->application) {
            return null;
        }
        $application = Application::query()->where('public_id', $this->application)->firstOrFail();
        try {
            app(ApplicationLifecycleService::class)->authorizeReviewer(auth()->user(), $application);
        } catch (AuthorizationException) {
            abort(404);
        }

        $application->load(['institution', 'region', 'manifest.module', 'manifest.retirement',
            'submissions' => fn ($q) => $q->orderByDesc('submission_sequence'),
            'history' => fn ($q) => $q->latest('created_at')]);
        if (auth()->user()->can('applications.corrections.request')) {
            $application->load('correctionRequests.items');
        }

        return $application;
    }

    #[Computed]
    public function submittedCopy(): ApplicationSubmission
    {
        $application = $this->selected;
        abort_unless($application, 404);
        app(ApplicationLifecycleService::class)->authorizeReviewer(auth()->user(), $application);
        $copy = $application->submissions()->when($this->submission,
            fn ($query) => $query->where('public_id', $this->submission),
            fn ($query) => $query->whereKey($application->current_submission_id))->firstOrFail();

        $copy->load('requirements.requirement');
        if (auth()->user()->can('applications.intake.answers.view')) {
            $copy->load(['answers' => fn ($query) => $query->where('is_applicable', true)->with('question.options')]);
        }
        if (auth()->user()->can('applications.intake.evidence.open')) {
            $copy->load('requirements.items.revision.provider');
        }

        return $copy;
    }

    public function beginScreening(): void
    {
        $this->resetErrorBag();
        $application = $this->selected();
        abort_unless($application, 404);
        try {
            app(ApplicationLifecycleService::class)->beginScreening(auth()->user(), $application);
            unset($this->selected, $this->submittedCopy);
            Notification::make()->title('Screening started')->body('You can now review the submitted answers and documents.')->success()->send();
        } catch (ValidationException $exception) {
            $this->reportValidationErrors($exception);
        } catch (\Throwable $exception) {
            $this->reportStaffFailure($exception);
        }
    }

    public function requestCorrection(): void
    {
        $this->resetErrorBag();
        $application = $this->selected();
        abort_unless($application, 404);
        try {
            app(ApplicationLifecycleService::class)->requestCorrection(auth()->user(), $application, $this->correctionSummary, [[
                'target_kind' => $this->correctionTarget,
                'form_question_id' => $this->correctionTarget === 'question' ? $this->correctionQuestion : null,
                'documentary_requirement_id' => $this->correctionTarget === 'document_requirement' ? $this->correctionRequirement : null,
                'issue_category' => $this->correctionCategory,
                'instruction' => $this->correctionInstruction,
            ]]);
            $this->reset(['correctionSummary', 'correctionInstruction', 'correctionQuestion', 'correctionRequirement']);
            unset($this->selected, $this->submittedCopy);
            Notification::make()->title('Corrections requested')->body('The applicant can now prepare a new working revision.')->success()->send();
        } catch (ValidationException $exception) {
            $this->reportValidationErrors($exception);
        } catch (\Throwable $exception) {
            $this->reportStaffFailure($exception);
        }
    }

    private function reportValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }

        Notification::make()
            ->title('Action could not be completed')
            ->body((string) collect($exception->errors())->flatten()->first())
            ->danger()
            ->send();
    }
}
