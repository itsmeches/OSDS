<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\ScopesReviewerQueue;
use App\Models\EvidenceReferenceRevision;
use App\Models\SyntheticEvidenceReviewCase;
use App\Services\Authorization\ReviewerQueueScope;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

class SyntheticEvidenceReviews extends Page
{
    use ScopesReviewerQueue;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $navigationLabel = 'Assigned test reviews';

    protected static ?string $title = 'Assigned test reviews';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.synthetic-evidence-reviews';

    #[Url]
    public ?string $case = null;

    public array $findingType = [];

    public array $outcome = [];

    public array $comment = [];

    public array $document = [];

    #[Locked]
    public ?string $findingConfirmation = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('evidence.reviews.view');
    }

    protected function queueQuery(): Builder
    {
        return app(ReviewerQueueScope::class)->evidenceReviews(
            SyntheticEvidenceReviewCase::query()->with(['institution', 'module', 'region'])->withExists('newerCopies'), auth()->user(),
        );
    }

    #[Computed]
    public function cases(): LengthAwarePaginator
    {
        $query = $this->filterQueue($this->queueQuery(), 'region_id');
        if ($this->status === 'superseded') {
            $query->whereHas('newerCopies');
        } elseif ($this->status === 'current') {
            $query->whereDoesntHave('newerCopies');
        } elseif ($this->status !== '') {
            $query->whereRaw('1 = 0');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10, page: $this->getPage());
    }

    #[Computed]
    public function selected(): ?SyntheticEvidenceReviewCase
    {
        if (! $this->case) {
            return null;
        } $case = SyntheticEvidenceReviewCase::query()->where('public_id', $this->case)->firstOrFail();
        app(SyntheticEvidenceReviewService::class)->authorizeReviewer(auth()->user(), $case);

        $case->load(['institution', 'module', 'region', 'newerCopies', 'requirements.requirement']);
        if (auth()->user()->can('evidence.reviews.open')) {
            $case->load('requirements.items.revision.provider');
        }
        if (auth()->user()->can('evidence.review.history.view')) {
            $case->load('requirements.findings.reviewer');
        }

        return $case;
    }

    public function saveFinding(int $requirementId, ?string $revisionPublicId = null): void
    {
        $this->resetErrorBag();
        $this->findingConfirmation = null;
        $case = $this->selected();
        abort_unless($case, 404);
        $requirement = $case->requirements()->findOrFail($requirementId);
        $revisionPublicId ??= $this->document[$requirementId] ?? null;
        $revision = $revisionPublicId ? EvidenceReferenceRevision::where('public_id', $revisionPublicId)->firstOrFail() : null;
        try {
            app(SyntheticEvidenceReviewService::class)->finding(auth()->user(), $requirement, $revision, $this->findingType[$requirementId] ?? '', $this->outcome[$requirementId] ?? '', $this->comment[$requirementId] ?? null);
            unset($this->comment[$requirementId]);
            unset($this->selected);
            $this->findingConfirmation = 'Test finding saved. You can inspect the recorded result in finding history.';
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError('finding.'.$requirementId.'.'.$field, $messages[0]);
            }
        } catch (\Throwable $exception) {
            $this->reportStaffFailure($exception);
        }
    }
}
