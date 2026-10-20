<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Identity\AccountGovernance;

class UserRegionAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('region_scopes.assign');
    }

    public function view(User $user, UserRegionAssignment $assignment): bool
    {
        return $user->isActive() && $user->can('region_scopes.assign') && app(ScopeAuthorizer::class)->canAccessRegion($user, $assignment->region_id);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('region_scopes.assign');
    }

    public function update(User $user, UserRegionAssignment $assignment): bool
    {
        if (! $this->view($user, $assignment)) {
            return false;
        }
        // Do not attach a User aggregate to the public Filament record while
        // checking target authority (getRecord is a callable page helper).
        $target = $assignment->user()->firstOrFail();

        return ! $user->is($target)
            && $assignment->revoked_at === null && ($assignment->ends_at === null || $assignment->ends_at->isFuture())
            && app(AccountGovernance::class)->canManageTargetRole($user, $target);
    }

    public function delete(User $user, UserRegionAssignment $assignment): bool
    {
        return false;
    }
}
