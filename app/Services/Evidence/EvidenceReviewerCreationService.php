<?php

namespace App\Services\Evidence;

use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class EvidenceReviewerCreationService
{
    public function create(User $actor, array $data): SyntheticEvidenceReviewerAssignment
    {
        return DB::transaction(function () use ($actor, $data): SyntheticEvidenceReviewerAssignment {
            $actor = User::findOrFail($actor->id);
            if (auth()->id() !== $actor->id || ! $actor->isActive()
                || ! $actor->can('platform.admin.access')
                || ! $actor->can('evidence.reviewer.assignments.manage')
                || ! app(ApplicantSandboxGuard::class)->allowed()) {
                throw new AuthorizationException;
            }
            $data = validator($data, [
                'reviewer_id' => 'required|integer|exists:users,id',
                'region_id' => 'required|integer|exists:regions,id',
                'module_id' => 'required|integer|exists:modules,id',
                'starts_at' => 'required|date',
                'ends_at' => 'nullable|date|after:starts_at',
                'reason' => 'required|string|max:500',
            ])->validate();
            // Only allow declared creation fields. Attribution and identity cannot
            // be supplied through forged form state. The existing observer is
            // the sole mandatory audit writer inside this transaction.
            $data['granted_by'] = $actor->id;

            return SyntheticEvidenceReviewerAssignment::create($data);
        });
    }
}
