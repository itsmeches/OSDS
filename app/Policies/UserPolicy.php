<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->isActive() && $user->can('users.view') && $this->sharesScope($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->isActive() && $user->can('users.manage') && $this->sharesScope($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function restore(User $user, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }

    private function sharesScope(User $actor, User $target): bool
    {
        $actorRegions = $actor->regionAssignments()->whereNull('revoked_at')->pluck('region_id');

        return $target->is($actor) || $target->regionAssignments()->whereNull('revoked_at')->whereIn('region_id', $actorRegions)->exists();
    }
}
