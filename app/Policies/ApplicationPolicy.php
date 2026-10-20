<?php

namespace App\Policies;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Authorization\ScopeAuthorizer;

class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        return app(ApplicantSandboxGuard::class)->allowed()
            && $user->isActive()
            && $user->is_synthetic
            && $user->can('applicant.portal.access')
            && $user->can('applications.track.own')
            && $application->applicant_id === $user->id
            && $application->institution->mayUseApplicantSandbox()
            && $application->manifest->channel === 'applicant_sandbox'
            && app(ScopeAuthorizer::class)->hasEffectiveRepresentation($user, $application->institution_id)
            && ApplicantSandboxEligibilityGrant::query()
                ->where('institution_id', $application->institution_id)
                ->where('module_id', $application->module_id)
                ->whereNull('revoked_at')
                ->where('starts_at', '<=', now())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->exists();
    }
}
