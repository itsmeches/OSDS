<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FormDefinitionPolicy extends QuestionnaireConfigurationPolicy
{
    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model) && ! $model->versions()->whereIn('state', ['published', 'retired'])->exists();
    }
}
