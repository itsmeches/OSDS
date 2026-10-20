<?php

namespace App\Services\Authorization;

use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use Illuminate\Database\Eloquent\Builder;

class ReviewerQueueScope
{
    public function __construct(private ApplicantSandboxGuard $sandbox, private ScopeAuthorizer $scope) {}

    public function applications(Builder $query, ?User $user): Builder
    {
        return $this->apply($query, $user, 'applications.intake.view', SyntheticApplicationReviewerAssignment::class, 'routing_region_id');
    }

    public function evidenceReviews(Builder $query, ?User $user): Builder
    {
        return $this->apply($query, $user, 'evidence.reviews.view', SyntheticEvidenceReviewerAssignment::class, 'region_id');
    }

    private function apply(Builder $query, ?User $user, string $permission, string $assignmentModel, string $regionColumn): Builder
    {
        if (! $user || ! $this->sandbox->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can($permission)) {
            return $query->whereRaw('1 = 0');
        }

        $pairs = $assignmentModel::query()
            ->where('reviewer_id', $user->id)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get(['region_id', 'module_id'])
            ->filter(fn ($assignment) => $this->scope->canAccessRegion($user, (int) $assignment->region_id));

        if ($pairs->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $authorized) use ($pairs, $regionColumn): void {
            foreach ($pairs as $pair) {
                $authorized->orWhere(fn (Builder $exact) => $exact
                    ->where($regionColumn, $pair->region_id)
                    ->where('module_id', $pair->module_id));
            }
        });
    }
}
