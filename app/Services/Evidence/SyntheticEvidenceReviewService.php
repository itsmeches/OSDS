<?php

namespace App\Services\Evidence;

use App\Models\ApplicationDraft;
use App\Models\EvidenceReferenceRevision;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\SyntheticEvidenceReviewFinding;
use App\Models\SyntheticEvidenceReviewItem;
use App\Models\SyntheticEvidenceReviewRequirement;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyntheticEvidenceReviewService
{
    public function __construct(private EvidenceReferenceService $evidence, private AuditRecorder $audit, private ScopeAuthorizer $scope) {}

    public function createCopy(User $user, ApplicationDraft $draft, int $expected, string $idempotencyKey, ?SyntheticEvidenceReviewCase $supersedes = null): SyntheticEvidenceReviewCase
    {
        return DB::transaction(function () use ($user, $draft, $expected, $idempotencyKey, $supersedes) {
            $draft = ApplicationDraft::query()->with(['institution', 'answers', 'manifest.retirement', 'manifest.formVersion.sections.questions', 'manifest.requirementSetVersion.requirements'])->lockForUpdate()->findOrFail($draft->id);
            $this->evidence->authorizeOwner($user, $draft);
            if (! $user->can('evidence.review.copy.create')) {
                throw new AuthorizationException;
            }
            $prior = SyntheticEvidenceReviewCase::query()->where(['application_draft_id' => $draft->id, 'applicant_id' => $user->id, 'idempotency_key' => $idempotencyKey])->first();
            if ($prior) {
                return $prior;
            }
            if ($draft->lock_version !== $expected) {
                throw ValidationException::withMessages(['conflict' => 'This draft was updated in another tab. Reload before sending documents for test review.']);
            }
            if ($draft->manifest->retirement || $draft->manifest->effective_until?->isPast()) {
                throw ValidationException::withMessages(['review' => 'This questionnaire version is no longer editable.']);
            }
            $missing = $this->evidence->missing($draft);
            if ($missing) {
                throw ValidationException::withMessages(['review' => 'Add all required document links before sending documents for test review.']);
            }
            if ($supersedes && $supersedes->application_draft_id !== $draft->id) {
                throw new AuthorizationException;
            }
            $answerHash = hash('sha256', json_encode($draft->answers->sortBy('form_question_id')->map(fn ($a) => [$a->form_question_id, $a->value])->values(), JSON_THROW_ON_ERROR));
            $case = SyntheticEvidenceReviewCase::query()->create(['application_draft_id' => $draft->id, 'publication_manifest_id' => $draft->publication_manifest_id, 'module_id' => $draft->module_id, 'institution_id' => $draft->institution_id, 'region_id' => $draft->institution->primary_region_id, 'applicant_id' => $user->id, 'draft_lock_version' => $draft->lock_version, 'answer_state_hash' => $answerHash, 'created_by' => $user->id, 'supersedes_case_id' => $supersedes?->id, 'idempotency_key' => $idempotencyKey]);
            foreach ($draft->manifest->requirementSetVersion->requirements as $r) {
                $applicable = $this->evidence->applicable($draft, $r);
                $refs = $applicable ? $this->evidence->activeReferences($draft->id, $r->id) : collect();
                $cr = SyntheticEvidenceReviewRequirement::query()->create(['case_id' => $case->id, 'documentary_requirement_id' => $r->id, 'is_applicable' => $applicable, 'is_required' => $r->is_required, 'minimum_count' => $r->minimum_evidence, 'maximum_count' => $r->maximum_evidence, 'active_count' => $refs->count(), 'sort_order' => $r->sort_order]);
                foreach ($refs as $ref) {
                    SyntheticEvidenceReviewItem::query()->create(['case_requirement_id' => $cr->id, 'evidence_reference_revision_id' => $ref->latestRevision->id]);
                }
            }
            $this->audit->record($supersedes ? 'evidence.test_review_copy_superseded' : 'evidence.test_review_copy_created', 'success', $user, $case, $case->region_id, $case->institution_id, ['manifest_id' => $case->publication_manifest_id, 'module_id' => $case->module_id, 'lock_version' => $case->draft_lock_version, 'supersedes_case_id' => $supersedes?->id]);

            return $case;
        });
    }

    public function authorizeReviewer(User $user, SyntheticEvidenceReviewCase $case, string $permission = 'evidence.reviews.view'): void
    {
        $assigned = SyntheticEvidenceReviewerAssignment::query()->where(['reviewer_id' => $user->id, 'region_id' => $case->region_id, 'module_id' => $case->module_id])->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
        if (! app(ApplicantSandboxGuard::class)->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can($permission) || ! $assigned || ! $this->scope->canAccessRegion($user, $case->region_id)) {
            throw new AuthorizationException;
        }
    }

    public function finding(User $user, SyntheticEvidenceReviewRequirement $requirement, ?EvidenceReferenceRevision $revision, string $type, string $outcome, ?string $comment = null, ?SyntheticEvidenceReviewFinding $supersedes = null): SyntheticEvidenceReviewFinding
    {
        return DB::transaction(function () use ($user, $requirement, $revision, $type, $outcome, $comment, $supersedes): SyntheticEvidenceReviewFinding {
            $user = $user->fresh();
            $requirement = SyntheticEvidenceReviewRequirement::query()->findOrFail($requirement->id);
            $case = SyntheticEvidenceReviewCase::query()->findOrFail($requirement->case_id);
            $this->authorizeReviewer($user, $case, 'evidence.review.findings.create');
            if ($revision && ! SyntheticEvidenceReviewItem::query()->where(['case_requirement_id' => $requirement->id, 'evidence_reference_revision_id' => $revision->id])->exists()) {
                throw new AuthorizationException;
            }
            validator(compact('type', 'outcome', 'comment'), ['type' => 'required|in:accessibility,completeness,document_match', 'outcome' => 'required|in:meets_test_check,needs_attention,unable_to_access,not_reviewed', 'comment' => 'nullable|string|max:2000'])->validate();

            $finding = SyntheticEvidenceReviewFinding::query()->create(['case_requirement_id' => $requirement->id, 'evidence_reference_revision_id' => $revision?->id, 'reviewer_id' => $user->id, 'finding_type' => $type, 'outcome' => $outcome, 'comment' => $comment, 'supersedes_finding_id' => $supersedes?->id, 'correlation_id' => (string) Str::uuid(), 'reviewed_at' => now()]);
            $this->audit->record('evidence.test_finding_created', 'success', $user, $finding, $case->region_id, $case->institution_id, ['case_id' => $case->id, 'requirement_id' => $requirement->documentary_requirement_id, 'finding_type' => $type, 'outcome' => $outcome]);

            return $finding;
        });
    }
}
