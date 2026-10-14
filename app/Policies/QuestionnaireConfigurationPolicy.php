<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('questionnaires.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->can('questionnaires.draft.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->create($user) && ($model->state ?? 'draft') === 'draft';
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
