<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;
use Carbon\CarbonImmutable;

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
        return $user->isActive() && collect(config('account-governance.creation_permissions'))->contains(fn (string $permission) => $user->can($permission));
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
        if (app(ScopeAuthorizer::class)->hasNationwideAccess($actor)) {
            return true;
        }

        $now = CarbonImmutable::now();
        $actorRegions = $actor->regionAssignments()->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->pluck('region_id');

        return $target->is($actor) || $target->regionAssignments()->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->whereIn('region_id', $actorRegions)->exists();
    }
}
