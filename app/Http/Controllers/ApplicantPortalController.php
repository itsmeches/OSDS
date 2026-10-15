<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDraft;
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
        $drafts = ApplicationDraft::with(['module', 'institution', 'manifest.retirement'])->where('created_by', $u->id)
            ->whereHas('institution.representatives', fn ($query) => $query->whereKey($u->id)->whereNotNull('institution_representatives.verified_at')->whereNull('institution_representatives.revoked_at')->where('institution_representatives.starts_at', '<=', now())->where(fn ($dates) => $dates->whereNull('institution_representatives.ends_at')->orWhere('institution_representatives.ends_at', '>', now())))
            ->latest('updated_at')->get();

        return view('applicant.home', compact('drafts'));
    }

    public function start(ApplicantSandboxGuard $g)
    {
        $u = $this->user($g);
        $institutions = $u->representedInstitutions()->wherePivotNotNull('verified_at')->wherePivotNull('revoked_at')->where('is_synthetic', true)->get();
        $manifests = PublicationManifest::with('module')->where('channel', 'applicant_sandbox')->where('effective_from', '<=', now())->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>', now()))->whereDoesntHave('retirement')->get();

        return view('applicant.start', compact('institutions', 'manifests'));
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
}
