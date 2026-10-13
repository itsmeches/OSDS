<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('regions.view');
    }

    public function view(User $user, Region $region): bool
    {
        return $user->can('regions.view') && app(ScopeAuthorizer::class)->canAccessRegion($user, $region->id);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('regions.manage');
    }

    public function update(User $user, Region $region): bool
    {
        return $user->can('regions.manage') && app(ScopeAuthorizer::class)->canAccessRegion($user, $region->id);
    }

    public function delete(User $user, Region $region): bool
    {
        return false;
    }

    public function restore(User $user, Region $region): bool
    {
        return false;
    }

    public function forceDelete(User $user, Region $region): bool
    {
        return false;
    }
}
