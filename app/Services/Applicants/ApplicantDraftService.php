<?php

namespace App\Services\Applicants;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftAnswer;
use App\Models\ApplicationWorkingRevision;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\PublicationManifest;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Questionnaires\ConditionalLogic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicantDraftService
{
    public function __construct(private ApplicantSandboxGuard $guard, private AuditRecorder $audit, private ConditionalLogic $conditions) {}

    public function start(User $user, Institution $institution, PublicationManifest $manifest): ApplicationDraft
    {
        $this->base($user, $institution, 'applications.draft.create');

        return DB::transaction(function () use ($user, $institution, $manifest) {
            $manifest = PublicationManifest::query()->with('retirement')->lockForUpdate()->findOrFail($manifest->id);
            if ($manifest->channel !== 'applicant_sandbox' || $manifest->retirement || $manifest->effective_from->isFuture() || ($manifest->effective_until && $manifest->effective_until->isPast())) {
                $this->invalid('No active applicant questionnaire is available.');
            }
            $eligible = ApplicantSandboxEligibilityGrant::query()->where('institution_id', $institution->id)->where('module_id', $manifest->module_id)->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
            if (! $eligible) {
                throw new AuthorizationException;
            }
            if (Application::query()->where(['institution_id' => $institution->id, 'module_id' => $manifest->module_id, 'publication_manifest_id' => $manifest->id])->whereIn('state', ['draft', 'submitted', 'under_screening', 'correction_requested', 'resubmitted', 'approved', 'rejected'])->exists()) {
                $this->invalid('An active test application already exists for this organization and questionnaire.');
            }
            $application = Application::query()->create(['module_id' => $manifest->module_id, 'publication_manifest_id' => $manifest->id, 'institution_id' => $institution->id, 'applicant_id' => $user->id, 'state' => 'draft', 'lock_version' => 1]);
            $draft = ApplicationDraft::query()->create(['application_id' => $application->id, 'module_id' => $manifest->module_id, 'publication_manifest_id' => $manifest->id, 'institution_id' => $institution->id, 'created_by' => $user->id, 'status' => 'draft', 'lock_version' => 1]);
            $working = ApplicationWorkingRevision::query()->create(['application_id' => $application->id, 'application_draft_id' => $draft->id, 'revision_number' => 1, 'purpose' => 'initial', 'state' => 'open', 'created_by' => $user->id, 'created_at' => now()]);
            $application->update(['current_working_revision_id' => $working->id]);
            $this->event($draft, $user, 'created', 1);
            $this->audit->record('application_draft.created', 'success', $user, $draft, $institution->primary_region_id, $institution->id, ['manifest_id' => $manifest->id]);

            return $draft;
        });
    }

    public function save(User $user, ApplicationDraft $draft, array $answers, int $expectedVersion, string $key): int
    {
        return DB::transaction(function () use ($user, $draft, $answers, $expectedVersion, $key) {
            $draft = ApplicationDraft::query()->with(['manifest.retirement', 'manifest.formVersion.sections.questions.options', 'institution'])->lockForUpdate()->findOrFail($draft->id);
            $this->base($user, $draft->institution, 'applications.draft.update');
            if ($draft->created_by !== $user->id) {
                throw new AuthorizationException;
            }
            if (! ApplicationWorkingRevision::query()->where(['application_draft_id' => $draft->id, 'state' => 'open'])->exists()) {
                $this->invalid('This submitted working revision is read-only.');
            }
            if ($draft->manifest->retirement || $draft->manifest->effective_until?->isPast()) {
                $this->invalid('This questionnaire version is read-only because its publication ended.');
            }
            $prior = DB::table('application_draft_idempotency_keys')->where(['application_draft_id' => $draft->id, 'user_id' => $user->id, 'operation' => 'save', 'idempotency_key' => $key])->first();
            if ($prior) {
                return (int) $prior->result_lock_version;
            }
            if ($draft->lock_version !== $expectedVersion) {
                $this->invalid('This draft was updated in another tab. Reload the latest saved version.', 'conflict');
            }
            $questions = $draft->manifest->formVersion->sections->flatMap->questions->keyBy('key');
            foreach ($answers as $questionKey => $value) {
                $question = $questions->get($questionKey);
                if (! $question) {
                    $this->invalid('An answer does not belong to this questionnaire.');
                }
                $this->validateValue($question->type, $value, $question->options->pluck('key')->all());
                ApplicationDraftAnswer::query()->updateOrCreate(['application_draft_id' => $draft->id, 'form_question_id' => $question->id], ['value' => $value, 'updated_by' => $user->id]);
            }
            $version = $draft->lock_version + 1;
            $draft->update(['lock_version' => $version, 'last_saved_at' => now()]);
            DB::table('application_draft_idempotency_keys')->insert(['application_draft_id' => $draft->id, 'user_id' => $user->id, 'idempotency_key' => $key, 'operation' => 'save', 'result_lock_version' => $version, 'created_at' => now()]);
            $this->event($draft, $user, 'saved', $version);

            return $version;
        });
    }

    public function missing(ApplicationDraft $draft): array
    {
        $draft->loadMissing('answers', 'manifest.formVersion.sections.questions');
        $values = [];
        foreach ($draft->answers as $answer) {
            $values[$answer->form_question_id] = $answer->value;
        }
        $keyed = [];
        $missing = [];
        foreach ($draft->manifest->formVersion->sections->flatMap->questions as $question) {
            $value = $values[$question->id] ?? null;
            if ($this->conditions->evaluate($question->condition_rules, $keyed) && $question->is_required && ($value === null || $value === '' || $value === [])) {
                $missing[] = $question->label;
            } $keyed[$question->key] = $value;
        }

        return $missing;
    }

    private function base(User $user, Institution $institution, string $permission): void
    {
        if (! $this->guard->allowed() || ! $user->isActive() || ! $user->is_synthetic || ! $user->can($permission) || ! $institution->mayUseApplicantSandbox()) {
            throw new AuthorizationException;
        }
        $represented = InstitutionRepresentative::query()->where(['user_id' => $user->id, 'institution_id' => $institution->id])->whereNotNull('verified_at')->whereNull('revoked_at')->where('starts_at', '<=', now())->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
        if (! $represented) {
            throw new AuthorizationException;
        }
    }

    private function validateValue(string $type, mixed $value, array $options): void
    {
        if (is_string($value) && mb_strlen($value) > 10000) {
            $this->invalid('An answer is too long.');
        }
        if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->invalid('Enter a whole number.');
        }
        if ($type === 'decimal' && ! is_numeric($value)) {
            $this->invalid('Enter a number.');
        }
        if ($type === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->invalid('Enter a valid email address.');
        }
        if ($type === 'single_choice' && ! in_array($value, $options, true)) {
            $this->invalid('Select a valid option.');
        }
        if ($type === 'multiple_choice' && (! is_array($value) || array_diff($value, $options))) {
            $this->invalid('Select valid options.');
        }
    }

    private function event(ApplicationDraft $draft, User $user, string $event, int $version): void
    {
        DB::table('application_draft_events')->insert(['application_draft_id' => $draft->id, 'actor_id' => $user->id, 'event' => $event, 'lock_version' => $version, 'correlation_id' => (string) Str::uuid(), 'metadata' => json_encode([]), 'created_at' => now()]);
    }

    private function invalid(string $message, string $key = 'draft'): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
