<?php

namespace App\Services\Evidence;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\ApplicationDraft;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\EvidenceReference;
use App\Models\EvidenceReferenceRevision;
use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Questionnaires\ConditionalLogic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceReferenceService
{
    public function __construct(private ApplicantSandboxGuard $guard, private GoogleEvidenceLocatorParser $parser, private EvidenceLocatorCipher $cipher, private ConditionalLogic $conditions, private AuditRecorder $audit) {}

    public function add(User $user, ApplicationDraft $draft, DocumentaryRequirement $requirement, string $url, int $expected, string $key): array
    {
        return $this->mutate($user, $draft, $requirement, null, 'add', $url, $expected, $key);
    }

    public function replace(User $user, ApplicationDraft $draft, EvidenceReference $reference, string $url, int $expected, string $key): array
    {
        return $this->mutate($user, $draft, $reference->requirement, $reference, 'replace', $url, $expected, $key);
    }

    public function remove(User $user, ApplicationDraft $draft, EvidenceReference $reference, int $expected, string $key): array
    {
        return $this->mutate($user, $draft, $reference->requirement, $reference, 'remove', null, $expected, $key);
    }

    private function mutate(User $user, ApplicationDraft $draft, DocumentaryRequirement $requirement, ?EvidenceReference $reference, string $operation, ?string $url, int $expected, string $key): array
    {
        return DB::transaction(function () use ($user, $draft, $requirement, $reference, $operation, $url, $expected, $key) {
            $draft = ApplicationDraft::query()->with(['institution', 'manifest.retirement', 'manifest.formVersion.sections.questions', 'manifest.requirementSetVersion.requirements.providers', 'answers'])->lockForUpdate()->findOrFail($draft->id);
            $this->authorizeOwner($user, $draft);
            if ($draft->manifest->retirement || $draft->manifest->effective_until?->isPast()) {
                $this->invalid('This questionnaire version is read-only.');
            }
            $prior = DB::table('evidence_mutation_idempotency_keys')->where(['application_draft_id' => $draft->id, 'user_id' => $user->id, 'operation' => $operation, 'idempotency_key' => $key])->first();
            if ($prior) {
                return ['lock_version' => (int) $prior->result_lock_version, 'reference_id' => $prior->evidence_reference_id];
            }
            if ($draft->lock_version !== $expected) {
                $this->invalid('This draft was updated in another tab. Your link was not discarded. Reload before trying again.', 'conflict');
            }
            $requirement = $draft->manifest->requirementSetVersion->requirements->firstWhere('id', $requirement->id);
            if (! $requirement || ! $this->applicable($draft, $requirement)) {
                $this->invalid('This document requirement is not currently applicable.');
            }
            if ($reference && ($reference->application_draft_id !== $draft->id || $reference->documentary_requirement_id !== $requirement->id)) {
                throw new AuthorizationException;
            }
            $payload = [];
            $provider = null;
            if ($operation !== 'remove') {
                $parsed = $this->parser->parse((string) $url);
                $provider = EvidenceProviderType::query()->where('code', $parsed->providerCode)->where('is_enabled', true)->first();
                if (! $provider || ! $requirement->providers->contains('id', $provider->id)) {
                    $this->invalid('This Google file type is not accepted for this requirement.');
                }
                $payload = $this->cipher->encrypt($parsed);
                $duplicate = EvidenceReference::query()->with('latestRevision')->where('application_draft_id', $draft->id)->get()->contains(fn ($candidate) => $candidate->latestRevision?->action !== 'removed' && $candidate->latestRevision?->locator_fingerprint === $payload['locator_fingerprint'] && (! $reference || $candidate->id !== $reference->id));
                if ($duplicate) {
                    $this->invalid('That Google file is already referenced in this draft.');
                }
                if ($operation === 'add' && $this->activeReferences($draft->id, $requirement->id)->count() >= $requirement->maximum_evidence) {
                    $this->invalid('The maximum number of links has already been added.');
                }
            }
            if (! $reference) {
                $reference = EvidenceReference::query()->create(['application_draft_id' => $draft->id, 'documentary_requirement_id' => $requirement->id, 'created_by' => $user->id]);
            }
            $revision = EvidenceReferenceRevision::query()->create(array_merge(['evidence_reference_id' => $reference->id, 'revision_number' => (int) $reference->revisions()->max('revision_number') + 1, 'action' => $operation === 'add' ? 'added' : ($operation === 'replace' ? 'replaced' : 'removed'), 'evidence_provider_type_id' => $provider?->id, 'created_by' => $user->id, 'draft_lock_version' => $draft->lock_version + 1, 'created_at' => now()], $payload));
            $version = $draft->lock_version + 1;
            $draft->update(['lock_version' => $version, 'last_saved_at' => now()]);
            DB::table('evidence_mutation_idempotency_keys')->insert(['application_draft_id' => $draft->id, 'user_id' => $user->id, 'operation' => $operation, 'idempotency_key' => $key, 'result_lock_version' => $version, 'evidence_reference_id' => $reference->id, 'evidence_reference_revision_id' => $revision->id, 'created_at' => now()]);
            DB::table('application_draft_events')->insert(['application_draft_id' => $draft->id, 'actor_id' => $user->id, 'event' => 'evidence_'.$operation, 'lock_version' => $version, 'correlation_id' => (string) Str::uuid(), 'metadata' => json_encode(['requirement_id' => $requirement->id, 'provider_code' => $provider?->code]), 'created_at' => now()]);
            $this->audit->record('evidence.reference_'.$operation, 'success', $user, $reference, $draft->institution->primary_region_id, $draft->institution_id, ['requirement_id' => $requirement->id, 'provider_code' => $provider?->code, 'lock_version' => $version]);

            return ['lock_version' => $version, 'reference_id' => $reference->id];
        });
    }

    public function activeReferences(int $draftId, int $requirementId)
    {
        return EvidenceReference::query()->with(['latestRevision.provider'])->where(['application_draft_id' => $draftId, 'documentary_requirement_id' => $requirementId])->get()->filter(fn ($r) => $r->latestRevision?->action !== 'removed')->values();
    }

    public function applicable(ApplicationDraft $draft, DocumentaryRequirement $requirement): bool
    {
        $values = $draft->answers->keyBy('form_question_id');
        $keyed = [];
        foreach ($draft->manifest->formVersion->sections->flatMap->questions as $q) {
            $keyed[$q->key] = $values->get($q->id)?->value;
        }

        return $this->conditions->evaluate($requirement->condition_rules, $keyed);
    }

    public function missing(ApplicationDraft $draft): array
    {
        $draft->loadMissing(['answers', 'manifest.formVersion.sections.questions', 'manifest.requirementSetVersion.requirements']);
        $missing = [];
        foreach ($draft->manifest->requirementSetVersion->requirements as $r) {
            if ($this->applicable($draft, $r) && $r->is_required && $this->activeReferences($draft->id, $r->id)->count() < $r->minimum_evidence) {
                $missing[] = $r->label;
            }
        }

        return $missing;
    }

    public function authorizeOwner(User $user, ApplicationDraft $draft): void
    {
        $institution = $draft->institution;
        $represented = InstitutionRepresentative::query()->where(['user_id' => $user->id, 'institution_id' => $institution->id])->whereNotNull('verified_at')->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
        $eligible = ApplicantSandboxEligibilityGrant::query()->where(['institution_id' => $institution->id, 'module_id' => $draft->module_id])->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
        if (! $this->guard->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can('evidence.references.manage') || $draft->created_by !== $user->id || $draft->manifest->channel !== 'applicant_sandbox' || ! $institution->mayUseApplicantSandbox() || ! $represented || ! $eligible) {
            throw new AuthorizationException;
        }
    }

    private function invalid(string $message, string $key = 'evidence'): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
