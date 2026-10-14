<?php

namespace App\Policies;

use App\Models\ConfigurationReviewRequest;
use App\Models\User;

class ConfigurationReviewRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->can('questionnaires.review') || $user->can('questionnaires.review.submit'));
    }

    public function view(User $user, ConfigurationReviewRequest $request): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('questionnaires.review.submit');
    }

    public function update(User $user, ConfigurationReviewRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, ConfigurationReviewRequest $request): bool
    {
        return false;
    }
}
