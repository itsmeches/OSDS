<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstitutionCreationService
{
    public function create(User $actor, array $attributes): Institution
    {
        return DB::transaction(function () use ($actor, $attributes): Institution {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
            $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)
                ->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();

            if (auth()->id() !== $actor->id || ! $actor->isActive() || ! $actor->can('platform.admin.access')
                || ! $actor->can('create', Institution::class)) {
                throw new AuthorizationException;
            }

            $data = validator($attributes, [
                'name' => 'required|string|max:255',
                'official_id' => ['nullable', 'string', 'max:255', Rule::unique('institutions', 'official_id')],
                'primary_region_id' => 'nullable|integer|exists:regions,id',
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ])->validate();
            if (($data['primary_region_id'] ?? null) !== null
                && ! app(StaffListScope::class)->regions(Region::query(), $actor, 'institutions.manage')->whereKey($data['primary_region_id'])->exists()) {
                throw ValidationException::withMessages(['primary_region_id' => 'The selected primary region is invalid.']);
            }

            // Preserve defaults/UUID and the unchanged model guard and sole Core audit.
            return Institution::create($data);
        }, 1);
    }
}
