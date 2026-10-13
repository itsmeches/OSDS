<?php

namespace App\Policies;

use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;

class InstitutionRepresentativePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('institution_representatives.assign');
    }

    public function view(User $user, InstitutionRepresentative $grant): bool
    {
        return $user->can('institution_representatives.assign') && app(ScopeAuthorizer::class)->canAccessInstitution($user, $grant->institution);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('institution_representatives.assign');
    }

    public function update(User $user, InstitutionRepresentative $grant): bool
    {
        return $this->view($user, $grant);
    }

    public function delete(User $user, InstitutionRepresentative $grant): bool
    {
        return false;
    }
}
