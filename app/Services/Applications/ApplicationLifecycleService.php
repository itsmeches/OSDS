<?php

namespace App\Services\Applications;

use App\Models\Application;
use App\Models\ApplicationCorrectionItem;
use App\Models\ApplicationCorrectionRequest;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftAnswer;
use App\Models\ApplicationSubmission;
use App\Models\ApplicationSubmissionAnswer;
use App\Models\ApplicationSubmissionEvidenceItem;
use App\Models\ApplicationSubmissionRequirement;
use App\Models\ApplicationWorkingRevision;
use App\Models\EvidenceReference;
use App\Models\EvidenceReferenceRevision;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Questionnaires\ConditionalLogic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationLifecycleService
{
    public function __construct(private ApplicantSandboxGuard $guard, private ApplicantDraftService $drafts, private EvidenceReferenceService $evidence, private ConditionalLogic $conditions, private ScopeAuthorizer $scope, private AuditRecorder $audit) {}

    public function submit(User $user, Application $application, int $expectedApplicationVersion, int $expectedDraftVersion, string $key): ApplicationSubmission
    {
        return DB::transaction(function () use ($user, $application, $expectedApplicationVersion, $expectedDraftVersion, $key) {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            $prior = ApplicationSubmission::query()->where(['application_id' => $application->id, 'submitted_by' => $user->id, 'idempotency_key' => $key])->first();
            if ($prior) {
                return $prior;
            }
            if (! $this->guard->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can($application->state === 'correction_requested' ? 'applications.resubmit' : 'applications.submit') || $application->applicant_id !== $user->id) {
                throw new AuthorizationException;
            }
            if (! in_array($application->state, ['draft', 'correction_requested'], true) || $application->lock_version !== $expectedApplicationVersion) {
                $this->invalid('This test application changed in another tab. Reload before submitting.', 'conflict');
            }
            $working = ApplicationWorkingRevision::query()->where(['id' => $application->current_working_revision_id, 'state' => 'open'])->lockForUpdate()->firstOrFail();
            $draft = ApplicationDraft::query()->with(['institution', 'manifest.retirement', 'manifest.formVersion.sections.questions.options', 'manifest.requirementSetVersion.requirements.providers', 'answers', 'evidenceReferences.latestRevision'])->lockForUpdate()->findOrFail($working->application_draft_id);
            $this->evidence->authorizeOwner($user, $draft);
            if ($draft->lock_version !== $expectedDraftVersion) {
                $this->invalid('Your saved draft changed in another tab. Reload before submitting.', 'conflict');
            }
            if ($draft->manifest->retirement || $draft->manifest->effective_until?->isPast()) {
                $this->invalid('This questionnaire version has ended. Your application is preserved, but submission is unavailable.');
            }
            $missing = array_merge($this->drafts->missing($draft), $this->evidence->missing($draft));
            if ($missing) {
                $this->invalid('Complete all required questions and documents before submitting.');
            }
            $sequence = (int) $application->submissions()->max('submission_sequence') + 1;
            $answers = [];
            $keyed = [];
            $order = 0;
            $saved = $draft->answers->keyBy('form_question_id');
            foreach ($draft->manifest->formVersion->sections->flatMap->questions as $q) {
                $value = $saved->get($q->id)?->value;
                $applicable = $this->conditions->evaluate($q->condition_rules, $keyed);
                $answers[] = ['question' => $q, 'value' => $value, 'applicable' => $applicable, 'order' => ++$order];
                $keyed[$q->key] = $value;
            }
            $requirements = [];
            $evidenceRows = [];
            foreach ($draft->manifest->requirementSetVersion->requirements as $r) {
                $applicable = $this->evidence->applicable($draft, $r);
                $refs = $applicable ? $this->evidence->activeReferences($draft->id, $r->id) : collect();
                $requirements[] = ['requirement' => $r, 'applicable' => $applicable, 'count' => $refs->count()];
                foreach ($refs as $ref) {
                    $evidenceRows[] = ['requirement_id' => $r->id, 'revision' => $ref->latestRevision];
                }
            }
            $answerHash = $this->hash(collect($answers)->map(fn ($x) => [$x['question']->key, $x['value'], $x['applicable']])->all());
            $requirementHash = $this->hash(collect($requirements)->map(fn ($x) => [$x['requirement']->key, $x['applicable'], $x['count']])->all());
            $evidenceHash = $this->hash(collect($evidenceRows)->map(fn ($x) => [$x['requirement_id'], $x['revision']->locator_fingerprint, $x['revision']->revision_number])->all());
            $correctionId = $working->correction_request_id;
            $submission = ApplicationSubmission::query()->create(['application_id' => $application->id, 'working_revision_id' => $working->id, 'source_draft_id' => $draft->id, 'submission_sequence' => $sequence, 'publication_manifest_id' => $draft->publication_manifest_id, 'module_id' => $draft->module_id, 'institution_id' => $draft->institution_id, 'routing_region_id' => $application->routing_region_id ?? $draft->institution->primary_region_id, 'applicant_id' => $user->id, 'applicant_name_snapshot' => $user->name, 'organization_name_snapshot' => $draft->institution->display_name ?: $draft->institution->name, 'organization_type_snapshot' => $draft->institution->organization_type, 'source_draft_lock_version' => $draft->lock_version, 'answer_hash' => $answerHash, 'requirement_hash' => $requirementHash, 'evidence_hash' => $evidenceHash, 'combined_hash' => hash('sha256', $answerHash.$requirementHash.$evidenceHash), 'submitted_by' => $user->id, 'submitted_at' => now(), 'prior_submission_id' => $application->current_submission_id, 'answered_correction_request_id' => $correctionId, 'idempotency_key' => $key, 'correlation_id' => (string) Str::uuid()]);
            foreach ($answers as $x) {
                ApplicationSubmissionAnswer::query()->create(['submission_id' => $submission->id, 'form_question_id' => $x['question']->id, 'question_key' => $x['question']->key, 'value' => $x['value'], 'is_applicable' => $x['applicable'], 'sort_order' => $x['order']]);
            }
            foreach ($requirements as $x) {
                $r = $x['requirement'];
                $sr = ApplicationSubmissionRequirement::query()->create(['submission_id' => $submission->id, 'documentary_requirement_id' => $r->id, 'is_applicable' => $x['applicable'], 'is_required' => $r->is_required, 'minimum_count' => $r->minimum_evidence, 'maximum_count' => $r->maximum_evidence, 'active_count' => $x['count'], 'sort_order' => $r->sort_order]);
                foreach (collect($evidenceRows)->where('requirement_id', $r->id) as $e) {
                    ApplicationSubmissionEvidenceItem::query()->create(['submission_requirement_id' => $sr->id, 'evidence_reference_revision_id' => $e['revision']->id]);
                }
            }
            $working->update(['state' => 'consumed', 'consumed_at' => now(), 'consumed_by_submission_id' => $submission->id]);
            $from = $application->state;
            $to = $from === 'draft' ? 'submitted' : 'resubmitted';
            $application->update(['state' => $to, 'routing_region_id' => $submission->routing_region_id, 'current_submission_id' => $submission->id, 'current_working_revision_id' => null, 'lock_version' => $application->lock_version + 1]);
            $this->history($application, $from, $to, $to, $user, $submission, 'Test application submitted.');
            $this->audit->record('application.'.$to, 'success', $user, $submission, $submission->routing_region_id, $submission->institution_id, ['application_id' => $application->id, 'submission_sequence' => $sequence, 'manifest_id' => $submission->publication_manifest_id]);

            return $submission;
        });
    }

    public function beginScreening(User $reviewer, Application $application): void
    {
        DB::transaction(function () use ($reviewer, $application) {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            $this->authorizeReviewer($reviewer, $application, 'applications.screening.start');
            if (! in_array($application->state, ['submitted', 'resubmitted'], true)) {
                $this->invalid('This application is not awaiting screening.');
            }$from = $application->state;
            $application->update(['state' => 'under_screening', 'lock_version' => $application->lock_version + 1]);
            DB::table('application_screening_events')->insert(['application_id' => $application->id, 'submission_id' => $application->current_submission_id, 'event' => 'screening_started', 'actor_id' => $reviewer->id, 'correlation_id' => (string) Str::uuid(), 'created_at' => now()]);
            $this->history($application, $from, 'under_screening', 'screening_started', $reviewer, ApplicationSubmission::find($application->current_submission_id), 'Screening started.');
        });
    }

    public function requestCorrection(User $reviewer, Application $application, string $summary, array $items): ApplicationCorrectionRequest
    {
        return DB::transaction(function () use ($reviewer, $application, $summary, $items) {
            $application = Application::query()->with('manifest.retirement')->lockForUpdate()->findOrFail($application->id);
            $this->authorizeReviewer($reviewer, $application, 'applications.corrections.request');
            if ($application->state !== 'under_screening') {
                $this->invalid('Corrections can only be requested during screening.');
            }if ($application->manifest->retirement || $application->manifest->effective_until?->isPast()) {
                $this->invalid('This questionnaire version has ended. The application is preserved, but corrections are temporarily unavailable.');
            }validator(['summary' => $summary, 'items' => $items], ['summary' => 'required|string|max:2000', 'items' => 'required|array|min:1', 'items.*.target_kind' => 'required|in:question,document_requirement,general', 'items.*.issue_category' => 'required|string|max:40', 'items.*.instruction' => 'required|string|max:2000'])->validate();
            $submission = ApplicationSubmission::findOrFail($application->current_submission_id);
            $validQuestions = $submission->answers()->pluck('form_question_id')->map(fn ($id) => (int) $id)->all();
            $validRequirements = $submission->requirements()->pluck('documentary_requirement_id')->map(fn ($id) => (int) $id)->all();
            foreach ($items as &$item) {
                if ($item['target_kind'] === 'question') {
                    if (! is_numeric($item['form_question_id'] ?? null)) {
                        $this->invalid('Select the questionnaire answer that needs correction.', 'items');
                    }
                    $item['form_question_id'] = (int) $item['form_question_id'];
                    $item['documentary_requirement_id'] = null;
                    if (! in_array($item['form_question_id'], $validQuestions, true)) {
                        $this->invalid('The selected question is not part of this submitted questionnaire. Refresh the case and try again.', 'items');
                    }
                } elseif ($item['target_kind'] === 'document_requirement') {
                    if (! is_numeric($item['documentary_requirement_id'] ?? null)) {
                        $this->invalid('Select the required document that needs correction.', 'items');
                    }
                    $item['documentary_requirement_id'] = (int) $item['documentary_requirement_id'];
                    $item['form_question_id'] = null;
                    if (! in_array($item['documentary_requirement_id'], $validRequirements, true)) {
                        $this->invalid('The selected document is not part of this submitted application. Refresh the case and try again.', 'items');
                    }
                } else {
                    $item['form_question_id'] = null;
                    $item['documentary_requirement_id'] = null;
                }
            }
            unset($item);
            $request = ApplicationCorrectionRequest::query()->create(['application_id' => $application->id, 'submission_id' => $submission->id, 'requested_by' => $reviewer->id, 'applicant_summary' => $summary, 'correlation_id' => (string) Str::uuid(), 'requested_at' => now()]);
            foreach ($items as $i => $item) {
                ApplicationCorrectionItem::query()->create(['correction_request_id' => $request->id, 'target_kind' => $item['target_kind'], 'form_question_id' => $item['form_question_id'] ?? null, 'documentary_requirement_id' => $item['documentary_requirement_id'] ?? null, 'issue_category' => $item['issue_category'], 'instruction' => $item['instruction'], 'sort_order' => $i + 1]);
            }
            $source = ApplicationDraft::with(['answers', 'evidenceReferences.latestRevision'])->findOrFail($submission->source_draft_id);
            $draft = ApplicationDraft::query()->create(['application_id' => $application->id, 'module_id' => $application->module_id, 'publication_manifest_id' => $application->publication_manifest_id, 'institution_id' => $application->institution_id, 'created_by' => $application->applicant_id, 'status' => 'draft', 'lock_version' => 1]);
            foreach ($source->answers as $a) {
                ApplicationDraftAnswer::query()->create(['application_draft_id' => $draft->id, 'form_question_id' => $a->form_question_id, 'value' => $a->value, 'updated_by' => $application->applicant_id]);
            }foreach ($source->evidenceReferences as $ref) {
                if (! $ref->latestRevision || $ref->latestRevision->action === 'removed') {
                    continue;
                }$new = EvidenceReference::query()->create(['application_draft_id' => $draft->id, 'documentary_requirement_id' => $ref->documentary_requirement_id, 'created_by' => $application->applicant_id]);
                $r = $ref->latestRevision;
                EvidenceReferenceRevision::query()->create(['public_id' => (string) Str::uuid(), 'evidence_reference_id' => $new->id, 'revision_number' => 1, 'action' => 'added', 'evidence_provider_type_id' => $r->evidence_provider_type_id, 'locator_ciphertext' => $r->locator_ciphertext, 'encryption_key_version' => $r->encryption_key_version, 'locator_fingerprint' => $r->locator_fingerprint, 'created_by' => $application->applicant_id, 'draft_lock_version' => 1, 'source_evidence_revision_id' => $r->id, 'created_at' => now()]);
            }
            $working = ApplicationWorkingRevision::query()->create(['application_id' => $application->id, 'application_draft_id' => $draft->id, 'revision_number' => (int) ApplicationWorkingRevision::where('application_id', $application->id)->max('revision_number') + 1, 'purpose' => 'correction', 'state' => 'open', 'source_submission_id' => $submission->id, 'correction_request_id' => $request->id, 'created_by' => $application->applicant_id, 'created_at' => now()]);
            $application->update(['state' => 'correction_requested', 'current_working_revision_id' => $working->id, 'lock_version' => $application->lock_version + 1]);
            $this->history($application, 'under_screening', 'correction_requested', 'correction_requested', $reviewer, $submission, 'Corrections requested.');

            return $request;
        });
    }

    public function authorizeReviewer(User $user, Application $application, string $permission = 'applications.intake.view'): void
    {
        $assigned = SyntheticApplicationReviewerAssignment::query()->where(['reviewer_id' => $user->id, 'region_id' => $application->routing_region_id, 'module_id' => $application->module_id])->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
        if (! $this->guard->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can($permission) || ! $assigned || ! $this->scope->canAccessRegion($user, $application->routing_region_id)) {
            throw new AuthorizationException;
        }
    }

    private function history(Application $a, ?string $from, string $to, string $event, User $actor, ?ApplicationSubmission $s, string $summary): void
    {
        DB::table('application_state_history')->insert(['application_id' => $a->id, 'from_state' => $from, 'to_state' => $to, 'event' => $event, 'submission_id' => $s?->id, 'actor_id' => $actor->id, 'applicant_summary' => $summary, 'correlation_id' => (string) Str::uuid(), 'created_at' => now()]);
    }

    private function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function invalid(string $message, string $key = 'application'): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
