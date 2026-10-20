<?php

namespace App\Services\Authorization;

use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Identity\AccountGovernance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RegionalGrantCreationService
{
    public function create(User $actor, array $attributes): UserRegionAssignment
    {
        return DB::transaction(function () use ($actor, $attributes): UserRegionAssignment {
            $data = validator($attributes, [
                'user_id' => 'required|integer|exists:users,id',
                'region_id' => 'required|integer|exists:regions,id',
                'starts_at' => 'required|date',
                'ends_at' => 'nullable|date|after:starts_at',
            ])->validate();

            // Match existing governance lock order. A competing target mutation
            // or creation finishes before we read current identity/delegation.
            User::query()->whereIn('id', [$actor->id, $data['user_id']])->orderBy('id')->lockForUpdate()->get();
            $actor = User::findOrFail($actor->id);
            $target = User::findOrFail($data['user_id']);
            $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
            $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
            Region::query()->sharedLock()->findOrFail($data['region_id']);

            // Direct permission/role revocation also changes these pivot rows.
            // Lock current memberships before loading Spatie's fresh relations.
            $ids = [$actor->id, $target->id];
            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())
                ->whereIn('model_id', $ids)->orderBy('model_id')->orderBy('role_id')->sharedLock()->get();
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())
                ->where('model_id', $actor->id)->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles->where('model_id', $actor->id)->pluck('role_id'))
                ->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();

            if (auth()->id() !== $actor->id || ! $actor->isActive()
                || ! $actor->can('platform.admin.access') || ! $actor->can('region_scopes.assign')
                || $actor->is($target)
                || ! app(ScopeAuthorizer::class)->canAccessRegion($actor, $data['region_id'])
                || ! app(AccountGovernance::class)->canManageTargetRole($actor, $target)) {
                throw new AuthorizationException;
            }

            // No caller-supplied attribution, revocation or identity fields.
            // The registered CoreIdentityObserver is the sole mandatory writer.
            $data['granted_by'] = $actor->id;

            return UserRegionAssignment::create($data);
        });
    }
}
