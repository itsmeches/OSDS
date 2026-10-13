<?php

namespace App\Policies;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;

class AuditEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('audit.view');
    }

    public function view(User $user, AuditEvent $event): bool
    {
        if (! $user->can('audit.view')) {
            return false;
        }
        if ($event->region_id !== null) {
            return app(ScopeAuthorizer::class)->canAccessRegion($user, $event->region_id);
        }

        return $event->institution_id === null && $event->actor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function restore(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
