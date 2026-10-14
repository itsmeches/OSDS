<?php

namespace App\Services\Identity;

use App\Models\AccountStatusEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use App\Support\AccountRole;
use App\Support\AccountStatus;
use App\Support\FoundationPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountGovernance
{
    public function __construct(
        private readonly AuditRecorder $audit,
        private readonly ScopeAuthorizer $scopes,
    ) {}

    public function create(User $actor, AccountRole $role, array $attributes, ?int $regionId = null, ?int $institutionId = null): User
    {
        $permission = config("account-governance.creation_permissions.{$role->value}");
        $this->denyUnless(is_string($permission) && $actor->isActive() && $actor->can($permission));
        $this->denyUnless(strcasecmp((string) ($attributes['email'] ?? ''), $actor->email) !== 0);

        if ($role === AccountRole::School) {
            $institution = Institution::query()->findOrFail($institutionId);
            $this->denyUnless($this->scopes->canAccessInstitution($actor, $institution));
        } else {
            $this->denyUnless($regionId !== null && $this->scopes->canAccessRegion($actor, $regionId));
        }

        return DB::transaction(function () use ($actor, $role, $attributes, $regionId, $institutionId): User {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'status' => $role === AccountRole::School ? AccountStatus::PendingVerification->value : AccountStatus::Active->value,
            ]);
            $user->assignRole($role->value);

            if ($role === AccountRole::School) {
                InstitutionRepresentative::query()->create([
                    'user_id' => $user->id,
                    'institution_id' => $institutionId,
                    'granted_by' => $actor->id,
                    'starts_at' => now(),
                ]);
            } else {
                UserRegionAssignment::query()->create([
                    'user_id' => $user->id,
                    'region_id' => $regionId,
                    'granted_by' => $actor->id,
                    'starts_at' => now(),
                ]);
            }

            $this->audit->record('account.created', 'success', $actor, $user, $regionId, $institutionId, ['role' => $role->value]);
            $this->audit->record('role.assigned', 'success', $actor, $user, $regionId, $institutionId, ['role' => $role->value]);

            return $user->refresh();
        }, 3);
    }

    public function assignPrimaryRole(User $actor, User $target, AccountRole $role): void
    {
        $permission = config("account-governance.creation_permissions.{$role->value}");
        $this->denyUnless(! $actor->is($target) && $actor->can(FoundationPermission::AssignRoles->value));
        $this->denyUnless(is_string($permission) && $actor->can($permission));
        $this->denyUnless($this->canManageTargetRole($actor, $target));

        DB::transaction(function () use ($actor, $target, $role): void {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            $before = $locked->getRoleNames()->first();
            $locked->syncRoles([$role->value]);
            $this->audit->record('role.assigned', 'success', $actor, $locked, metadata: ['from' => $before, 'to' => $role->value]);
            $locked->invalidateSessions();
        }, 3);
    }

    public function verifySchool(User $actor, User $school, InstitutionRepresentative $representation): void
    {
        $this->denyUnless($actor->can(FoundationPermission::VerifySchoolAccounts->value));
        $this->denyUnless(! $actor->is($school) && $school->hasRole(AccountRole::School->value));
        $this->denyUnless($representation->user_id === $school->id && $this->scopes->canAccessInstitution($actor, $representation->institution));
        if (! config('account-governance.allow_school_creator_to_verify')) {
            $this->denyUnless($representation->granted_by !== $actor->id);
        }

        DB::transaction(function () use ($actor, $school, $representation): void {
            $locked = User::query()->lockForUpdate()->findOrFail($school->id);
            $this->denyUnless($locked->status === AccountStatus::PendingVerification->value);
            $representation->forceFill(['verified_at' => now(), 'verified_by' => $actor->id])->save();
            $locked->forceFill(['status' => AccountStatus::Active->value])->save();
            $correlationId = (string) Str::uuid();
            $this->statusEvent($locked, AccountStatus::PendingVerification, AccountStatus::Active, $actor, 'Institutional representative verified.', $correlationId);
            $this->audit->record('account.verified', 'success', $actor, $locked, $representation->institution->primary_region_id, $representation->institution_id, correlationId: $correlationId);
            $this->audit->record('account.activated', 'success', $actor, $locked, $representation->institution->primary_region_id, $representation->institution_id, correlationId: $correlationId);
        }, 3);
    }

    public function transition(User $actor, User $target, AccountStatus $to, string $reason): void
    {
        $permission = match ($to) {
            AccountStatus::Active => in_array($target->status, [AccountStatus::Suspended->value, AccountStatus::Inactive->value], true)
                ? FoundationPermission::ReactivateAccounts : FoundationPermission::ActivateAccounts,
            AccountStatus::Suspended => FoundationPermission::SuspendAccounts,
            AccountStatus::Inactive => FoundationPermission::DeactivateAccounts,
            AccountStatus::PendingVerification => throw new InvalidArgumentException('Pending verification is assigned only during SCHOOL account creation.'),
        };
        $this->denyUnless(! $actor->is($target) && $actor->can($permission->value) && trim($reason) !== '');
        $this->denyUnless($this->canManageTargetRole($actor, $target));

        DB::transaction(function () use ($actor, $target, $to, $reason): void {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            $from = AccountStatus::from($locked->status);
            $allowed = [
                AccountStatus::Active->value => [AccountStatus::Suspended, AccountStatus::Inactive],
                AccountStatus::Suspended->value => [AccountStatus::Active, AccountStatus::Inactive],
                AccountStatus::PendingVerification->value => [AccountStatus::Inactive],
                AccountStatus::Inactive->value => [AccountStatus::Active],
            ];
            $this->denyUnless(in_array($to, $allowed[$from->value], true));
            $correlationId = (string) Str::uuid();
            $locked->forceFill(['status' => $to->value])->save();
            $this->statusEvent($locked, $from, $to, $actor, $reason, $correlationId);
            $action = match ($to) {
                AccountStatus::Active => $from === AccountStatus::Suspended ? 'account.reactivated' : 'account.activated',
                AccountStatus::Suspended => 'account.suspended',
                AccountStatus::Inactive => 'account.deactivated',
                AccountStatus::PendingVerification => throw new InvalidArgumentException('Unsupported status transition.'),
            };
            $this->audit->record($action, 'success', $actor, $locked, metadata: ['reason' => $reason], correlationId: $correlationId);
        }, 3);
    }

    public function canManageTargetRole(User $actor, User $target): bool
    {
        $targetRole = $target->getRoleNames()->first();
        $permission = $targetRole === null ? null : config("account-governance.creation_permissions.{$targetRole}");

        return is_string($permission) && $actor->can($permission);
    }

    private function statusEvent(User $user, AccountStatus $from, AccountStatus $to, User $actor, string $reason, string $correlationId): void
    {
        AccountStatusEvent::query()->create([
            'user_id' => $user->id, 'from_status' => $from->value, 'to_status' => $to->value,
            'changed_by' => $actor->id, 'reason' => $reason, 'correlation_id' => $correlationId,
        ]);
    }

    private function denyUnless(bool $condition): void
    {
        if (! $condition) {
            throw new AuthorizationException('This account governance action is not authorized.');
        }
    }
}
