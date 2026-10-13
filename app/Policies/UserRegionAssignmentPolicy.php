<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Authorization\ScopeAuthorizer;

class UserRegionAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('region_scopes.assign');
    }

    public function view(User $user, UserRegionAssignment $assignment): bool
    {
        return $user->can('region_scopes.assign') && app(ScopeAuthorizer::class)->canAccessRegion($user, $assignment->region_id);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('region_scopes.assign');
    }

    public function update(User $user, UserRegionAssignment $assignment): bool
    {
        return $this->view($user, $assignment);
    }

    public function delete(User $user, UserRegionAssignment $assignment): bool
    {
        return false;
    }
}
