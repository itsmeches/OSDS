<?php

namespace App\Services\Authorization;

use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Identity\AccountGovernance;
use App\Support\FoundationPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AccessGovernance
{
    public function __construct(
        private readonly ScopeAuthorizer $scopes,
        private readonly AccountGovernance $accounts,
        private readonly AuditRecorder $audit,
    ) {}

    public function grantRegion(User $actor, User $target, Region $region, string $reason): UserRegionAssignment
    {
        $this->assertDelegation($actor, $target, FoundationPermission::AssignRegionScope);
        $this->denyUnless($this->scopes->canAccessRegion($actor, $region->id) && trim($reason) !== '');

        return DB::transaction(function () use ($actor, $target, $region, $reason): UserRegionAssignment {
            User::query()->lockForUpdate()->findOrFail($target->id);
            $grant = UserRegionAssignment::query()->create(['user_id' => $target->id, 'region_id' => $region->id, 'granted_by' => $actor->id, 'starts_at' => now()]);
            $this->audit->record('region_scope.granted', 'success', $actor, $grant, $region->id, metadata: ['reason' => $reason]);

            return $grant;
        }, 3);
    }

    public function revokeRegion(User $actor, UserRegionAssignment $grant, string $reason): void
    {
        $this->assertDelegation($actor, $grant->user, FoundationPermission::RevokeRegionScope);
        $this->denyUnless($this->scopes->canAccessRegion($actor, $grant->region_id) && trim($reason) !== '');
        DB::transaction(function () use ($actor, $grant, $reason): void {
            $locked = UserRegionAssignment::query()->lockForUpdate()->findOrFail($grant->id);
            $this->denyUnless($locked->revoked_at === null);
            $locked->update(['revoked_at' => now(), 'revoked_by' => $actor->id]);
            $locked->user->invalidateSessions();
            $this->audit->record('region_scope.revoked', 'success', $actor, $locked, $locked->region_id, metadata: ['reason' => $reason]);
        }, 3);
    }

    public function transferRegion(User $actor, User $target, UserRegionAssignment $from, Region $to, string $reason): UserRegionAssignment
    {
        $this->denyUnless($from->user_id === $target->id);

        return DB::transaction(function () use ($actor, $target, $from, $to, $reason): UserRegionAssignment {
            $this->revokeRegion($actor, $from, $reason);

            return $this->grantRegion($actor, $target, $to, $reason);
        }, 3);
    }

    public function grantInstitution(User $actor, User $target, Institution $institution, string $reason): InstitutionRepresentative
    {
        $this->assertDelegation($actor, $target, FoundationPermission::AssignInstitutionRepresentatives);
        $this->denyUnless($this->scopes->canAccessInstitution($actor, $institution) && trim($reason) !== '');

        return DB::transaction(function () use ($actor, $target, $institution, $reason): InstitutionRepresentative {
            User::query()->lockForUpdate()->findOrFail($target->id);
            $grant = InstitutionRepresentative::query()->create(['user_id' => $target->id, 'institution_id' => $institution->id, 'granted_by' => $actor->id, 'starts_at' => now()]);
            $this->audit->record('institution_representation.granted', 'success', $actor, $grant, $institution->primary_region_id, $institution->id, ['reason' => $reason]);

            return $grant;
        }, 3);
    }

    public function revokeInstitution(User $actor, InstitutionRepresentative $grant, string $reason): void
    {
        $this->assertDelegation($actor, $grant->user, FoundationPermission::RevokeInstitutionRepresentatives);
        $this->denyUnless($this->scopes->canAccessInstitution($actor, $grant->institution) && trim($reason) !== '');
        DB::transaction(function () use ($actor, $grant, $reason): void {
            $locked = InstitutionRepresentative::query()->lockForUpdate()->findOrFail($grant->id);
            $this->denyUnless($locked->revoked_at === null);
            $locked->update(['revoked_at' => now(), 'revoked_by' => $actor->id]);
            $locked->user->invalidateSessions();
            $this->audit->record('institution_representation.revoked', 'success', $actor, $locked, $locked->institution->primary_region_id, $locked->institution_id, ['reason' => $reason]);
        }, 3);
    }

    public function grantNationwide(User $actor, User $target, string $reason): NationwideAccessGrant
    {
        $this->assertDelegation($actor, $target, FoundationPermission::AssignNationwideScope);
        $this->denyUnless(trim($reason) !== '');

        return DB::transaction(function () use ($actor, $target, $reason): NationwideAccessGrant {
            User::query()->lockForUpdate()->findOrFail($target->id);
            $grant = NationwideAccessGrant::query()->create(['user_id' => $target->id, 'granted_by' => $actor->id, 'starts_at' => now(), 'reason' => $reason]);
            $this->audit->record('nationwide_scope.granted', 'success', $actor, $grant, metadata: ['reason' => $reason]);

            return $grant;
        }, 3);
    }

    public function revokeNationwide(User $actor, NationwideAccessGrant $grant, string $reason): void
    {
        $this->assertDelegation($actor, $grant->user, FoundationPermission::RevokeNationwideScope);
        $this->denyUnless(trim($reason) !== '');
        DB::transaction(function () use ($actor, $grant, $reason): void {
            $locked = NationwideAccessGrant::query()->lockForUpdate()->findOrFail($grant->id);
            $this->denyUnless($locked->revoked_at === null);
            $locked->update(['revoked_at' => now(), 'revoked_by' => $actor->id]);
            $locked->user->invalidateSessions();
            $this->audit->record('nationwide_scope.revoked', 'success', $actor, $locked, metadata: ['reason' => $reason]);
        }, 3);
    }

    private function assertDelegation(User $actor, User $target, FoundationPermission $permission): void
    {
        $this->denyUnless($actor->isActive() && ! $actor->is($target) && $actor->can($permission->value));
        $this->denyUnless($this->accounts->canManageTargetRole($actor, $target));
    }

    private function denyUnless(bool $condition): void
    {
        if (! $condition) {
            throw new AuthorizationException('This access governance action is not authorized.');
        }
    }
}
