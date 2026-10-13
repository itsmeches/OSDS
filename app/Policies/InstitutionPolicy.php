<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;

class InstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('institutions.view');
    }

    public function view(User $user, Institution $institution): bool
    {
        return $user->can('institutions.view') && app(ScopeAuthorizer::class)->canAccessInstitution($user, $institution);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('institutions.manage');
    }

    public function update(User $user, Institution $institution): bool
    {
        return $user->can('institutions.manage') && app(ScopeAuthorizer::class)->canAccessInstitution($user, $institution);
    }

    public function delete(User $user, Institution $institution): bool
    {
        return false;
    }

    public function restore(User $user, Institution $institution): bool
    {
        return false;
    }

    public function forceDelete(User $user, Institution $institution): bool
    {
        return false;
    }
}
