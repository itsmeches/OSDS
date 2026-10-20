<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ScopeAuthorizer
{
    public function hasNationwideAccess(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $user->nationwideAccessGrants()
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->exists();
    }

    public function canAccessRegion(User $user, int $regionId): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $this->hasNationwideAccess($user) || $user->regionAssignments()
            ->where('region_id', $regionId)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->exists();
    }

    public function canAccessInstitution(User $user, Institution $institution): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $represented = $this->hasEffectiveRepresentation($user, $institution->id);

        return $this->hasNationwideAccess($user) || $represented || ($institution->primary_region_id !== null
            && $this->canAccessRegion($user, $institution->primary_region_id));
    }

    public function hasEffectiveRepresentation(User $user, int $institutionId): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $user->representedInstitutions()
            ->whereKey($institutionId)
            ->wherePivotNull('revoked_at')
            ->wherePivotNotNull('verified_at')
            ->wherePivot('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('institution_representatives.ends_at')
                ->orWhere('institution_representatives.ends_at', '>', $now))
            ->exists();

    }

    public function effectiveRegionIds(User $user): Collection
    {
        if (! $user->isActive()) {
            return collect();
        }
        if ($this->hasNationwideAccess($user)) {
            return Region::query()->pluck('id');
        }
        $now = CarbonImmutable::now();

        return $user->regionAssignments()->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->pluck('region_id')->unique();
    }

    public function effectiveRepresentedInstitutionIds(User $user): Collection
    {
        if (! $user->isActive()) {
            return collect();
        }
        $now = CarbonImmutable::now();

        return $user->representedInstitutions()->wherePivotNull('revoked_at')->wherePivotNotNull('verified_at')
            ->wherePivot('starts_at', '<=', $now)
            ->where(fn ($dates) => $dates->whereNull('institution_representatives.ends_at')->orWhere('institution_representatives.ends_at', '>', $now))
            ->pluck('institutions.id');
    }
}
