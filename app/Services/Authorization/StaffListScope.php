<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class StaffListScope
{
    public function __construct(private ScopeAuthorizer $scope) {}

    public function regions(Builder $query, ?User $user, string $permission = 'regions.view'): Builder
    {
        if (! $this->allowed($user, $permission)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->qualifyColumn('id'), $this->scope->effectiveRegionIds($user));
    }

    public function institutions(Builder $query, ?User $user, string $permission = 'institutions.view'): Builder
    {
        return $this->allowed($user, $permission)
            ? $this->institutionScope($query, $user)
            : $query->whereRaw('1 = 0');
    }

    public function representatives(Builder $query, ?User $user): Builder
    {
        if (! $this->allowed($user, 'institution_representatives.assign')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('institution', fn (Builder $institutions) => $this->institutionScope($institutions, $user));
    }

    public function regionAssignments(Builder $query, ?User $user): Builder
    {
        if (! $this->allowed($user, 'region_scopes.assign')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('region_id', $this->scope->effectiveRegionIds($user));
    }

    public function auditEvents(Builder $query, ?User $user): Builder
    {
        if (! $this->allowed($user, 'audit.view')) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->can('audit.view.national') && $this->scope->hasNationwideAccess($user)) {
            return $query;
        }
        $regions = $this->scope->effectiveRegionIds($user);
        $institutions = $this->institutionScope(Institution::query(), $user)->select('id');

        return $query->where(fn (Builder $events) => $events
            ->whereIn('region_id', $regions)
            ->orWhere(fn (Builder $organization) => $organization->whereNull('region_id')->whereIn('institution_id', $institutions))
            ->orWhere(fn (Builder $own) => $own->whereNull('region_id')->whereNull('institution_id')->where('actor_id', $user->id)));
    }

    public function users(Builder $query, ?User $user, string $permission = 'users.view'): Builder
    {
        if (! $this->allowed($user, $permission)) {
            return $query->whereRaw('1 = 0');
        }
        if ($this->scope->hasNationwideAccess($user)) {
            return $query;
        }
        $regions = $this->scope->effectiveRegionIds($user);
        $now = CarbonImmutable::now();

        return $query->where(fn (Builder $users) => $users->whereKey($user->id)
            ->orWhereHas('regionAssignments', fn (Builder $grants) => $grants->whereIn('region_id', $regions)
                ->whereNull('revoked_at')->where('starts_at', '<=', $now)
                ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', $now))));
    }

    private function institutionScope(Builder $query, User $user): Builder
    {
        if ($this->scope->hasNationwideAccess($user)) {
            return $query;
        }
        $regions = $this->scope->effectiveRegionIds($user);
        $represented = $this->scope->effectiveRepresentedInstitutionIds($user);

        return $query->where(fn (Builder $institutions) => $institutions
            ->whereIn('primary_region_id', $regions)->orWhereIn($query->qualifyColumn('id'), $represented));
    }

    private function allowed(?User $user, string $permission): bool
    {
        return $user !== null && $user->isActive() && $user->can($permission);
    }
}
