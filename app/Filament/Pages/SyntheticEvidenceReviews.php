<?php

namespace App\Filament\Pages;

use App\Models\EvidenceReferenceRevision;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\SyntheticEvidenceReviewRequirement;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;

class SyntheticEvidenceReviews extends Page
{
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

    public static function canAccess(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('evidence.reviews.view');
    }

    public function cases(): Collection
    {
        $user = auth()->user();
        $assignments = SyntheticEvidenceReviewerAssignment::query()->where('reviewer_id', $user->id)->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->get();
        $pairs = $assignments->filter(fn ($a) => app(ScopeAuthorizer::class)->canAccessRegion($user, $a->region_id));

        return SyntheticEvidenceReviewCase::query()->where(function ($q) use ($pairs) {
            foreach ($pairs as $a) {
                $q->orWhere(fn ($x) => $x->where('region_id', $a->region_id)->where('module_id', $a->module_id));
            }
        })->latest('created_at')->get();
    }

    public function selected(): ?SyntheticEvidenceReviewCase
    {
        if (! $this->case) {
            return null;
        } $case = SyntheticEvidenceReviewCase::query()->with(['requirements.requirement', 'requirements.items.revision.provider', 'requirements.findings'])->where('public_id', $this->case)->firstOrFail();
        app(SyntheticEvidenceReviewService::class)->authorizeReviewer(auth()->user(), $case);

        return $case;
    }

    public function saveFinding(int $requirementId, ?string $revisionPublicId = null): void
    {
        $requirement = SyntheticEvidenceReviewRequirement::findOrFail($requirementId);
        $revision = $revisionPublicId ? EvidenceReferenceRevision::where('public_id', $revisionPublicId)->firstOrFail() : null;
        app(SyntheticEvidenceReviewService::class)->finding(auth()->user(), $requirement, $revision, $this->findingType[$requirementId] ?? 'completeness', $this->outcome[$requirementId] ?? 'not_reviewed', $this->comment[$requirementId] ?? null);
        unset($this->comment[$requirementId]);
        Notification::make()->title('Test finding saved')->success()->send();
    }
}
