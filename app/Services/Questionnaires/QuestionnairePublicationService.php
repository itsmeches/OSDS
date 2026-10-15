<?php

namespace App\Services\Questionnaires;

use App\Models\ConfigurationReviewApproval;
use App\Models\ConfigurationReviewRequest;
use App\Models\FormVersion;
use App\Models\PublicationManifest;
use App\Models\PublicationManifestRetirement;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Support\FoundationPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuestionnairePublicationService
{
    public function __construct(
        private readonly ConfigurationValidator $validator,
        private readonly ConfigurationHasher $hasher,
        private readonly AuditRecorder $audit,
    ) {}

    public function submitForReview(User $actor, FormVersion $form, RequirementSetVersion $requirements): ConfigurationReviewRequest
    {
        $this->authorize($actor, FoundationPermission::SubmitQuestionnaireReview);

        return DB::transaction(function () use ($actor, $form, $requirements) {
            $form = FormVersion::query()->lockForUpdate()->findOrFail($form->id);
            $requirements = RequirementSetVersion::query()->lockForUpdate()->findOrFail($requirements->id);
            if ($form->state !== 'draft' || $requirements->state !== 'draft') {
                $this->invalid('Only draft configurations may be submitted for review.');
            }
            $this->validator->validate($form, $requirements);
            $formHash = $this->hasher->form($form);
            $requirementHash = $this->hasher->requirements($requirements);
            $combinedHash = $this->hasher->combined($formHash, $requirementHash);
            $request = ConfigurationReviewRequest::query()->create([
                'module_id' => $requirements->module_id, 'form_version_id' => $form->id,
                'requirement_set_version_id' => $requirements->id, 'form_hash' => $formHash,
                'requirement_hash' => $requirementHash, 'combined_hash' => $combinedHash,
                'state' => 'pending', 'submitted_by' => $actor->id, 'submitted_at' => now(),
            ]);
            $form->update(['state' => 'in_review', 'configuration_hash' => $formHash, 'lock_version' => $form->lock_version + 1]);
            $requirements->update(['state' => 'in_review', 'configuration_hash' => $requirementHash, 'lock_version' => $requirements->lock_version + 1]);
            $this->audit->record('questionnaire.review_submitted', 'success', $actor, $request, metadata: ['module_id' => $requirements->module_id, 'combined_hash' => $combinedHash]);

            return $request;
        });
    }

    public function review(User $actor, ConfigurationReviewRequest $request, bool $approve, string $reference, ?string $comment = null): ConfigurationReviewApproval
    {
        $this->authorize($actor, FoundationPermission::ReviewQuestionnaires);
        if (trim($reference) === '') {
            $this->invalid('An approval or rejection reference is required.');
        }

        return DB::transaction(function () use ($actor, $request, $approve, $reference, $comment) {
            $request = ConfigurationReviewRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->state !== 'pending') {
                $this->invalid('Only a pending review may be decided.');
            }
            $form = FormVersion::query()->lockForUpdate()->findOrFail($request->form_version_id);
            $requirements = RequirementSetVersion::query()->lockForUpdate()->findOrFail($request->requirement_set_version_id);
            $this->assertCurrentHashes($request, $form, $requirements);
            $correlationId = (string) Str::uuid();
            $approval = ConfigurationReviewApproval::query()->create([
                'configuration_review_request_id' => $request->id, 'reviewed_by' => $actor->id,
                'outcome' => $approve ? 'approved' : 'rejected', 'approval_reference' => trim($reference),
                'comment' => $comment, 'approved_hash' => $request->combined_hash,
                'correlation_id' => $correlationId, 'reviewed_at' => now(),
            ]);
            $request->update(['state' => $approve ? 'approved' : 'rejected']);
            if (! $approve) {
                $form->update(['state' => 'draft', 'configuration_revision' => $form->configuration_revision + 1, 'configuration_hash' => null, 'lock_version' => $form->lock_version + 1]);
                $requirements->update(['state' => 'draft', 'configuration_revision' => $requirements->configuration_revision + 1, 'configuration_hash' => null, 'lock_version' => $requirements->lock_version + 1]);
            }
            $this->audit->record($approve ? 'questionnaire.review_approved' : 'questionnaire.review_rejected', 'success', $actor, $request, metadata: ['combined_hash' => $request->combined_hash, 'reference' => trim($reference)], correlationId: $correlationId);

            return $approval;
        });
    }

    public function publishSandbox(User $actor, ConfigurationReviewApproval $approval, mixed $effectiveFrom = null, mixed $effectiveUntil = null): PublicationManifest
    {
        $this->authorize($actor, FoundationPermission::PublishQuestionnaireSandbox);

        return DB::transaction(function () use ($actor, $approval, $effectiveFrom, $effectiveUntil) {
            $approval = ConfigurationReviewApproval::query()->lockForUpdate()->findOrFail($approval->id);
            if ($approval->outcome !== 'approved') {
                $this->invalid('Only an approved review can be published.');
            }
            $request = ConfigurationReviewRequest::query()->lockForUpdate()->findOrFail($approval->configuration_review_request_id);
            $form = FormVersion::query()->lockForUpdate()->findOrFail($request->form_version_id);
            $requirements = RequirementSetVersion::query()->lockForUpdate()->findOrFail($request->requirement_set_version_id);
            DB::select('SELECT pg_advisory_xact_lock(?)', [$request->module_id]);
            $this->assertCurrentHashes($request, $form, $requirements);
            if ($request->state !== 'approved' || $approval->approved_hash !== $request->combined_hash) {
                $this->invalid('The approval no longer matches this configuration.');
            }
            $activeExists = PublicationManifest::query()->where('module_id', $request->module_id)->where('channel', 'review_sandbox')
                ->whereDoesntHave('retirement')->exists();
            if ($activeExists) {
                $this->invalid('Retire the active sandbox publication before publishing another.');
            }
            $manifest = PublicationManifest::query()->create([
                'module_id' => $request->module_id, 'form_version_id' => $form->id,
                'requirement_set_version_id' => $requirements->id,
                'configuration_review_approval_id' => $approval->id, 'channel' => 'review_sandbox',
                'effective_from' => $effectiveFrom ?? now(), 'effective_until' => $effectiveUntil,
                'form_hash' => $request->form_hash, 'requirement_hash' => $request->requirement_hash,
                'combined_hash' => $request->combined_hash, 'published_by' => $actor->id, 'published_at' => now(),
            ]);
            $form->update(['state' => 'published']);
            $requirements->update(['state' => 'published']);
            $this->audit->record('questionnaire.sandbox_published', 'success', $actor, $manifest, metadata: ['module_id' => $request->module_id, 'combined_hash' => $request->combined_hash, 'channel' => 'review_sandbox']);

            return $manifest;
        });
    }

    public function publishApplicantSandbox(User $actor, ConfigurationReviewApproval $approval, mixed $effectiveFrom = null, mixed $effectiveUntil = null): PublicationManifest
    {
        $this->authorize($actor, FoundationPermission::PublishApplicantSandbox);
        if (! app(ApplicantSandboxGuard::class)->allowed()) {
            throw new AuthorizationException('Applicant sandbox publication requires an isolated non-production database.');
        }

        return DB::transaction(function () use ($actor, $approval, $effectiveFrom, $effectiveUntil) {
            $approval = ConfigurationReviewApproval::query()->lockForUpdate()->findOrFail($approval->id);
            $request = ConfigurationReviewRequest::query()->lockForUpdate()->findOrFail($approval->configuration_review_request_id);
            $form = FormVersion::query()->lockForUpdate()->findOrFail($request->form_version_id);
            $requirements = RequirementSetVersion::query()->lockForUpdate()->findOrFail($request->requirement_set_version_id);
            $this->assertCurrentHashes($request, $form, $requirements);
            if ($approval->outcome !== 'approved' || $request->state !== 'approved' || $approval->approved_hash !== $request->combined_hash) {
                $this->invalid('The approval no longer matches this configuration.');
            }
            DB::select('SELECT pg_advisory_xact_lock(?)', [$request->module_id]);
            if (PublicationManifest::query()->where('module_id', $request->module_id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->exists()) {
                $this->invalid('Retire the active applicant sandbox publication first.');
            }
            $manifest = PublicationManifest::query()->create(['module_id' => $request->module_id, 'form_version_id' => $form->id, 'requirement_set_version_id' => $requirements->id, 'configuration_review_approval_id' => $approval->id, 'channel' => 'applicant_sandbox', 'effective_from' => $effectiveFrom ?? now(), 'effective_until' => $effectiveUntil, 'form_hash' => $request->form_hash, 'requirement_hash' => $request->requirement_hash, 'combined_hash' => $request->combined_hash, 'published_by' => $actor->id, 'published_at' => now()]);
            $this->audit->record('questionnaire.applicant_sandbox_published', 'success', $actor, $manifest, metadata: ['module_id' => $request->module_id, 'combined_hash' => $request->combined_hash, 'channel' => 'applicant_sandbox']);

            return $manifest;
        });
    }

    public function retire(User $actor, PublicationManifest $manifest, string $reason): PublicationManifestRetirement
    {
        $this->authorize($actor, FoundationPermission::RetireQuestionnaires);
        if (trim($reason) === '') {
            $this->invalid('A retirement reason is required.');
        }

        return DB::transaction(function () use ($actor, $manifest, $reason) {
            $manifest = PublicationManifest::query()->lockForUpdate()->findOrFail($manifest->id);
            if ($manifest->retirement()->exists()) {
                $this->invalid('The publication is already retired.');
            }
            $correlationId = (string) Str::uuid();
            $retirement = PublicationManifestRetirement::query()->create(['publication_manifest_id' => $manifest->id, 'retired_by' => $actor->id, 'reason' => trim($reason), 'correlation_id' => $correlationId, 'retired_at' => now()]);
            $this->audit->record('questionnaire.sandbox_retired', 'success', $actor, $manifest, metadata: ['reason' => trim($reason)], correlationId: $correlationId);

            return $retirement;
        });
    }

    private function assertCurrentHashes(ConfigurationReviewRequest $request, FormVersion $form, RequirementSetVersion $requirements): void
    {
        $this->validator->validate($form, $requirements);
        $formHash = $this->hasher->form($form);
        $requirementsHash = $this->hasher->requirements($requirements);
        if ($request->form_hash !== $formHash || $request->requirement_hash !== $requirementsHash || $request->combined_hash !== $this->hasher->combined($formHash, $requirementsHash)) {
            $this->invalid('The reviewed configuration has changed. Submit it for review again.');
        }
    }

    private function authorize(User $actor, FoundationPermission $permission): void
    {
        if (! $actor->isActive() || ! $actor->can($permission->value)) {
            throw new AuthorizationException;
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['publication' => $message]);
    }
}
