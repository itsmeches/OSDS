<?php

namespace App\Policies;

use App\Models\PublicationManifest;
use App\Models\User;

class PublicationManifestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('questionnaires.publication.view');
    }

    public function view(User $user, PublicationManifest $manifest): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PublicationManifest $manifest): bool
    {
        return false;
    }

    public function delete(User $user, PublicationManifest $manifest): bool
    {
        return false;
    }
}
