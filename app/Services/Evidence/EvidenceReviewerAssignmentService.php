<?php

namespace App\Services\Evidence;

use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvidenceReviewerAssignmentService
{
    public function revoke(User $actor, SyntheticEvidenceReviewerAssignment $assignment, string $reason): void
    {
        DB::transaction(function () use ($actor, $assignment, $reason): void {
            $actor = User::query()->findOrFail($actor->id);
            $assignment = SyntheticEvidenceReviewerAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            // A concurrent revocation may hold this row while actor status or
            // authority changes. Re-read identity after acquiring the lock.
            $actor = User::query()->findOrFail($actor->id);
            if (auth()->id() !== $actor->id || ! $actor->isActive()
                || ! app(ApplicantSandboxGuard::class)->allowed()
                || ! $actor->can('evidence.reviewer.assignments.manage')
                || ! app(ScopeAuthorizer::class)->canAccessRegion($actor, $assignment->region_id)) {
                throw new AuthorizationException;
            }
            if ($assignment->revoked_at !== null) {
                throw ValidationException::withMessages(['revocation_reason' => 'This reviewer assignment is already revoked. Refresh to see its saved status.']);
            }
            validator(['revocation_reason' => $reason], ['revocation_reason' => 'required|string|max:500'])->validate();
            // The existing mandatory observer runs synchronously on this connection.
            // Do not record a second audit or retry an uncertain mutation here.
            $assignment->update(['revoked_at' => now(), 'revoked_by' => $actor->id, 'revocation_reason' => $reason]);
        });
    }
}
