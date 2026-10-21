<?php

namespace App\Services\Authorization;

use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegionUpdateService
{
    public function update(User $actor, Region $region, array $attributes): Region
    {
        return DB::transaction(function () use ($actor, $region, $attributes): Region {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
            $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
            $region = Region::query()->lockForUpdate()->findOrFail($region->id);
            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();

            if (auth()->id() !== $actor->id || ! $actor->isActive() || ! $actor->can('platform.admin.access')
                || ! $actor->can('update', $region)) {
                throw new AuthorizationException;
            }

            $data = validator($attributes, [
                'code' => ['required', 'string', 'max:32', Rule::unique('regions', 'code')->ignore($region->id)],
                'name' => 'required|string|max:255',
                'is_active' => 'required|boolean',
            ])->validate();

            // Preserve UUID/activity semantics and the sole synchronous Core audit.
            $region->update($data);

            return $region;
        }, 1);
    }
}
