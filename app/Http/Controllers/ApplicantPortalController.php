<?php

namespace App\Http\Controllers;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\ApplicationCorrectionRequest;
use App\Models\Institution;
use App\Models\PublicationManifest;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applicants\ApplicantSandboxGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicantPortalController
{
    private function user(ApplicantSandboxGuard $g)
    {
        abort_unless($g->allowed(), 404);
        $u = auth()->user();
        abort_unless($u && $u->isActive() && $u->is_synthetic && $u->can('applicant.portal.access'), 403);

        return $u;
    }

    public function home(ApplicantSandboxGuard $g)
    {
        $u = $this->user($g);
        $applications = Application::with(['institution', 'manifest.module', 'manifest.retirement', 'currentWorkingRevision.draft'])->where('applicant_id', $u->id)
            ->whereHas('institution.representatives', fn ($query) => $query->whereKey($u->id)->whereNotNull('institution_representatives.verified_at')->whereNull('institution_representatives.revoked_at')->where('institution_representatives.starts_at', '<=', now())->where(fn ($dates) => $dates->whereNull('institution_representatives.ends_at')->orWhere('institution_representatives.ends_at', '>', now())))
            ->latest('updated_at')->get();

        return view('applicant.home', compact('applications'));
    }

    public function start(ApplicantSandboxGuard $g)
    {
        $u = $this->user($g);
        $institutions = $u->representedInstitutions()
            ->wherePivotNotNull('verified_at')
            ->wherePivotNull('revoked_at')
            ->wherePivot('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('institution_representatives.ends_at')->orWhere('institution_representatives.ends_at', '>', now()))
            ->where('is_synthetic', true)
            ->get();

        $eligibleModuleIds = ApplicantSandboxEligibilityGrant::query()
            ->whereIn('institution_id', $institutions->modelKeys())
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get(['institution_id', 'module_id']);

        $modulesByInstitution = $eligibleModuleIds->groupBy('institution_id')
            ->map(fn ($grants) => $grants->pluck('module_id')->unique()->values());

        $manifests = PublicationManifest::with('module')
            ->whereIn('module_id', $eligibleModuleIds->pluck('module_id'))
            ->where('channel', 'applicant_sandbox')
            ->where('effective_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>', now()))
            ->whereDoesntHave('retirement')
            ->orderBy('effective_from')
            ->get();

        return view('applicant.start', compact('institutions', 'manifests', 'modulesByInstitution'));
    }

    public function createDraft(Request $r, ApplicantSandboxGuard $g, ApplicantDraftService $service): RedirectResponse
    {
        $u = $this->user($g);
        $data = $r->validate(['institution_id' => 'required|integer', 'manifest_id' => 'required|integer']);
        $draft = $service->start($u, Institution::findOrFail($data['institution_id']), PublicationManifest::findOrFail($data['manifest_id']));

        return redirect()->route('applicant.drafts.edit', $draft);
    }

    public function account(ApplicantSandboxGuard $g)
    {
        $u = $this->user($g);
        $institutions = $u->representedInstitutions()->get();

        return view('applicant.account', compact('institutions'));
    }

    public function show(Application $application, ApplicantSandboxGuard $g)
    {
        $u = $this->user($g);
        abort_unless($u->can('view', $application), 404);
        $application->load(['institution', 'manifest.retirement', 'currentSubmission', 'currentWorkingRevision.draft', 'history' => fn ($q) => $q->orderBy('created_at'), 'submissions' => fn ($q) => $q->orderByDesc('submission_sequence')]);
        $correction = ApplicationCorrectionRequest::with('items')->where('application_id', $application->id)->latest('id')->first();

        return view('applicant.track', compact('application', 'correction'));
    }
}
