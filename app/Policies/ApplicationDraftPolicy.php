<?php

namespace App\Policies;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\ApplicationDraft;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Authorization\ScopeAuthorizer;

class ApplicationDraftPolicy
{
    public function view(User $user, ApplicationDraft $draft): bool
    {
        return app(ApplicantSandboxGuard::class)->allowed()
            && $user->isActive()
            && $user->is_synthetic
            && $user->can('applicant.portal.access')
            && $user->can('applications.draft.view')
            && $draft->created_by === $user->id
            && app(ScopeAuthorizer::class)->hasEffectiveRepresentation($user, $draft->institution_id)
            && $draft->institution->mayUseApplicantSandbox()
            && $draft->manifest->channel === 'applicant_sandbox'
            && $draft->application()->where('applicant_id', $user->id)
                ->where('institution_id', $draft->institution_id)
                ->where('module_id', $draft->module_id)
                ->where('publication_manifest_id', $draft->publication_manifest_id)->exists()
            && ApplicantSandboxEligibilityGrant::query()
                ->where('institution_id', $draft->institution_id)
                ->where('module_id', $draft->module_id)
                ->whereNull('revoked_at')
                ->where('starts_at', '<=', now())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->exists();
    }

    public function update(User $user, ApplicationDraft $draft): bool
    {
        return $this->view($user, $draft) && $user->can('applications.draft.update');
    }
}
