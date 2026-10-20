<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Services\Identity\AccountGovernance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RepresentationCreationService
{
    public function create(User $actor, array $attributes): InstitutionRepresentative
    {
        return DB::transaction(function () use ($actor, $attributes): InstitutionRepresentative {
            $data = validator($attributes, [
                'user_id' => 'required|integer|exists:users,id',
                'institution_id' => 'required|integer|exists:institutions,id',
                'starts_at' => 'required|date',
                'ends_at' => 'nullable|date|after:starts_at',
            ])->validate();

            // Existing delegated-edit lock order. Recheck after waits rather
            // than trusting the actor/target loaded when the form was opened.
            User::query()->whereIn('id', [$actor->id, $data['user_id']])->orderBy('id')->lockForUpdate()->get();
            $actor = User::findOrFail($actor->id);
            $target = User::findOrFail($data['user_id']);
            $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
            $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
            InstitutionRepresentative::where('user_id', $actor->id)->orderBy('id')->lockForUpdate()->get();
            $institution = Institution::query()->sharedLock()->findOrFail($data['institution_id']);

            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())
                ->whereIn('model_id', [$actor->id, $target->id])->orderBy('model_id')->orderBy('role_id')->sharedLock()->get();
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())
                ->where('model_id', $actor->id)->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles->where('model_id', $actor->id)->pluck('role_id'))
                ->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();

            if (auth()->id() !== $actor->id || ! $actor->isActive()
                || ! $actor->can('platform.admin.access') || ! $actor->can('institution_representatives.assign')
                || $actor->is($target)
                || ! app(ScopeAuthorizer::class)->canAccessInstitution($actor, $institution)
                || ! app(AccountGovernance::class)->canManageTargetRole($actor, $target)) {
                throw new AuthorizationException;
            }

            // Verification/revocation and caller attribution are never creation
            // fields. The unchanged CoreIdentityObserver owns the sole audit.
            $data['granted_by'] = $actor->id;

            return InstitutionRepresentative::create($data);
        });
    }
}
