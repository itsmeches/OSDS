<?php

namespace App\Policies;

use App\Models\ApplicationDraft;
use App\Models\User;

class ApplicationDraftPolicy
{
    public function view(User $user, ApplicationDraft $draft): bool
    {
        return $user->isActive() && $user->is_synthetic && $user->can('applications.draft.view') && $draft->created_by === $user->id
            && $user->representedInstitutions()->whereKey($draft->institution_id)->wherePivotNotNull('verified_at')->wherePivotNull('revoked_at')
                ->wherePivot('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('institution_representatives.ends_at')->orWhere('institution_representatives.ends_at', '>', now()))->exists();
    }

    public function update(User $user, ApplicationDraft $draft): bool
    {
        return $this->view($user, $draft) && $user->can('applications.draft.update');
    }
}
