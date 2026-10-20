<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EvidenceReference;
use App\Models\EvidenceReferenceRevision;
use App\Models\SyntheticEvidenceReviewCase;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceLocatorCipher;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use Illuminate\Http\RedirectResponse;

class EvidenceLinkController extends Controller
{
    public function own(EvidenceReference $reference, EvidenceReferenceService $service, EvidenceLocatorCipher $cipher): RedirectResponse
    {
        $reference->load(['draft.institution', 'latestRevision']);
        abort_unless(auth()->user()->can('evidence.references.open.own'), 404);
        $service->authorizeOwner(auth()->user(), $reference->draft);
        abort_if(! $reference->latestRevision || $reference->latestRevision->action === 'removed', 404);

        return $this->redirect($reference->latestRevision, $cipher);
    }

    public function review(SyntheticEvidenceReviewCase $case, EvidenceReferenceRevision $revision, SyntheticEvidenceReviewService $service, EvidenceLocatorCipher $cipher): RedirectResponse
    {
        $service->authorizeReviewer(auth()->user(), $case, 'evidence.reviews.open');
        abort_unless($case->requirements()->whereHas('items', fn ($q) => $q->where('evidence_reference_revision_id', $revision->id))->exists(), 404);

        return $this->redirect($revision, $cipher);
    }

    public function application(Application $application, EvidenceReferenceRevision $revision, ApplicationLifecycleService $service, EvidenceLocatorCipher $cipher): RedirectResponse
    {
        $service->authorizeReviewer(auth()->user(), $application, 'applications.intake.evidence.open');
        abort_unless($application->currentSubmission?->requirements()->whereHas('items', fn ($query) => $query->where('evidence_reference_revision_id', $revision->id))->exists(), 404);

        return $this->redirect($revision, $cipher);
    }

    private function redirect(EvidenceReferenceRevision $revision, EvidenceLocatorCipher $cipher): RedirectResponse
    {
        $locator = $cipher->decrypt($revision->locator_ciphertext, $revision->encryption_key_version);

        return redirect()->away($locator->canonicalUrl())->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer']);
    }
}
