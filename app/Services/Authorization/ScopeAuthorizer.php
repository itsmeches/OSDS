<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\User;
use Carbon\CarbonImmutable;

class ScopeAuthorizer
{
    public function canAccessRegion(User $user, int $regionId): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $user->regionAssignments()
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

        $now = CarbonImmutable::now();
        $represented = $user->representedInstitutions()
            ->whereKey($institution->id)
            ->wherePivotNull('revoked_at')
            ->wherePivot('starts_at', '<=', $now)
            ->where(fn ($query) => $query->whereNull('institution_representatives.ends_at')
                ->orWhere('institution_representatives.ends_at', '>', $now))
            ->exists();

        return $represented || ($institution->primary_region_id !== null
            && $this->canAccessRegion($user, $institution->primary_region_id));
    }
}
