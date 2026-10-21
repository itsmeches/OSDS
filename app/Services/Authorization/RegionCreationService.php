<?php

namespace App\Services\Authorization;

use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegionCreationService
{
    public function create(User $actor, array $attributes): Region
    {
        return DB::transaction(function () use ($actor, $attributes): Region {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();

            if (auth()->id() !== $actor->id || ! $actor->isActive() || ! $actor->can('platform.admin.access')
                || ! $actor->can('create', Region::class)) {
                throw new AuthorizationException;
            }

            $data = validator($attributes, [
                'code' => ['required', 'string', 'max:32', Rule::unique('regions', 'code')],
                'name' => 'required|string|max:255',
                'is_active' => 'required|boolean',
            ])->validate();

            // Creation has no current-region scope prerequisite. Preserve the
            // UUID hook and sole synchronous Core observer audit unchanged.
            return Region::create($data);
        }, 1);
    }
}
