<?php

namespace App\Policies;

use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Identity\AccountGovernance;

class InstitutionRepresentativePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('institution_representatives.assign');
    }

    public function view(User $user, InstitutionRepresentative $grant): bool
    {
        return $user->isActive() && $user->can('institution_representatives.assign') && app(ScopeAuthorizer::class)->canAccessInstitution($user, $grant->institution()->firstOrFail());
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('institution_representatives.assign');
    }

    public function update(User $user, InstitutionRepresentative $grant): bool
    {
        if (! $this->view($user, $grant)) {
            return false;
        }
        $target = $grant->user()->firstOrFail();

        return ! $user->is($target)
            && $grant->revoked_at === null && ($grant->ends_at === null || $grant->ends_at->isFuture())
            && app(AccountGovernance::class)->canManageTargetRole($user, $target);
    }

    public function delete(User $user, InstitutionRepresentative $grant): bool
    {
        return false;
    }
}
