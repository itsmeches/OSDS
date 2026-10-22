<?php

namespace App\Services\Documents;

use App\Models\Application;
use App\Models\ApplicationSubmissionAnswer;
use App\Models\ConfigurationReviewApproval;
use App\Models\FormQuestion;
use App\Models\FormVersion;
use App\Models\IssuanceDefinition;
use App\Models\IssuanceRequest;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Questionnaires\ConditionalLogic;
use App\Services\Questionnaires\ConfigurationHasher;
use App\Services\Questionnaires\ConfigurationValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseIssuanceService
{
    public function actor(User $actor, ?string $staffPermission = null): User
    {
        $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
        $roles = DB::table('model_has_roles')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('role_id')->sharedLock()->pluck('role_id');
        DB::table('model_has_permissions')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('permission_id')->sharedLock()->get();
        DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();
        if (auth()->id() !== $fresh->id || ! app(ApplicantSandboxGuard::class)->allowed() || ! $fresh->isActive() || ! $fresh->is_synthetic
            || ($staffPermission && (! $fresh->can('platform.admin.access') || ! $fresh->can($staffPermission)))) {
            throw new AuthorizationException;
        }

        return $fresh;
    }

    public function authorizeCase(User $actor, Application $case, bool $write = false): void
    {
        Gate::forUser($actor)->authorize('view', $case);
        if ($write && ! $actor->can('issuance.requests.submit')) {
            throw new AuthorizationException;
        }
    }

    public function createDefinition(User $actor, array $input): IssuanceDefinition
    {
        return DB::transaction(function () use ($actor, $input) {
            $actor = $this->actor($actor, 'issuance.definitions.manage');
            $data = validator($input, ['title' => 'required|string|max:160', 'instructions' => 'nullable|string|max:2000', 'approval_id' => 'required|integer', 'source_form_id' => 'required|integer', 'name_question_id' => 'required|integer', 'address_question_id' => 'required|integer'])->validate();
            $approval = ConfigurationReviewApproval::with('request.formVersion.sections.questions.options', 'request.requirementSetVersion.requirements.providers')->findOrFail($data['approval_id']);
            $review = $approval->request;
            $source = FormVersion::with('definition')->findOrFail($data['source_form_id']);
            $module = OsdsModule::query()->lockForUpdate()->findOrFail($review->module_id);
            FormVersion::whereIn('id', [$source->id, $review->form_version_id])->orderBy('id')->lockForUpdate()->get();
            RequirementSetVersion::whereKey($review->requirement_set_version_id)->lockForUpdate()->firstOrFail();
            $review->refresh()->load('formVersion.sections.questions.options', 'requirementSetVersion.requirements.providers');
            $source->refresh()->load('definition');
            if (! in_array($module->code, ['registration', 'acsa'], true) || ! $module->is_active || $source->definition->module_id !== $module->id || $approval->outcome !== 'approved' || $review->state !== 'approved') {
                $this->invalid('Select a matching approved COR or ACSA configuration.');
            }
            $hasher = app(ConfigurationHasher::class);
            app(ConfigurationValidator::class)->validate($review->formVersion, $review->requirementSetVersion);
            if ($hasher->combined($hasher->form($review->formVersion), $hasher->requirements($review->requirementSetVersion)) !== $approval->approved_hash) {
                $this->invalid('The configuration changed after review. Request review of the exact versions.');
            }
            foreach (['name_question_id', 'address_question_id'] as $field) {
                if (! FormQuestion::whereKey($data[$field])->where('form_version_id', $source->id)->whereIn('type', ['short_text', 'long_text'])->exists()) {
                    $this->invalid('Identity bindings must select text answers in the exact application questionnaire.');
                }
            }
            $questions = $review->formVersion->sections->flatMap->questions->map(fn ($q) => ['key' => $q->key, 'label' => $q->label, 'help' => $q->help_text, 'type' => $q->type, 'required' => $q->is_required, 'condition' => $q->condition_rules, 'validation' => $q->validation_rules, 'options' => $q->options->map(fn ($o) => ['key' => $o->key, 'label' => $o->label])->all()])->values()->all();
            $requirements = $review->requirementSetVersion->requirements->map(fn ($r) => ['key' => $r->key, 'label' => $r->label, 'instructions' => $r->instructions, 'required' => $r->is_required, 'minimum' => $r->minimum_evidence, 'maximum' => $r->maximum_evidence, 'condition' => $r->condition_rules])->all();
            if (array_intersect(array_column($questions, 'key'), ['certificate_type', 'organization', 'address', 'institution_id', 'region_id', 'application_id', 'submission_id', 'decision_id', 'director_actor_id'])) {
                $this->invalid('Supplementary question keys cannot reuse protected identity fields. Rename those keys in a new reviewed questionnaire.');
            }
            $definition = ['title' => $data['title'], 'instructions' => $data['instructions'] ?? '', 'name_question_id' => $data['name_question_id'], 'address_question_id' => $data['address_question_id'], 'source_form_id' => $source->id, 'questions' => $questions, 'requirements' => $requirements, 'approved_configuration_hash' => $approval->approved_hash];
            $row = IssuanceDefinition::create(['module_id' => $module->id, 'version' => 1 + (int) IssuanceDefinition::where('module_id', $module->id)->max('version'), 'state' => 'draft', 'title' => $data['title'], 'instructions' => $data['instructions'] ?? '', 'configuration_review_approval_id' => $approval->id, 'source_form_version_id' => $source->id, 'definition' => $definition, 'definition_hash' => $this->hash($definition), 'created_by' => $actor->id]);
            $this->event($actor, $row, 'definition_created');

            return $row;
        }, 1);
    }

    public function transition(User $actor, IssuanceDefinition $definition, int $expectedVersion, string $to): IssuanceDefinition
    {
        return DB::transaction(function () use ($actor, $definition, $expectedVersion, $to) {
            $permission = match ($to) {
                'reviewed' => 'issuance.definitions.review', 'published','withdrawn' => 'issuance.definitions.publish', default => throw new AuthorizationException
            };
            $actor = $this->actor($actor, $permission);
            OsdsModule::whereKey($definition->module_id)->lockForUpdate()->firstOrFail();
            $row = IssuanceDefinition::lockForUpdate()->findOrFail($definition->id);
            $from = match ($to) {
                'reviewed' => 'draft', 'published' => 'reviewed', 'withdrawn' => 'published'
            };
            if ($row->lock_version !== $expectedVersion || $row->state !== $from) {
                $this->invalid('The definition changed. Reload its current version.');
            }
            if ($to === 'published' && IssuanceDefinition::where('module_id', $row->module_id)->where('state', 'published')->exists()) {
                $this->invalid('Withdraw the current issuance definition before publishing its replacement.');
            }
            $row->update(['state' => $to, 'lock_version' => $row->lock_version + 1]);
            $this->event($actor, $row, 'definition_'.$to);

            return $row;
        }, 1);
    }

    public function eligibility(Application $case, ?IssuanceDefinition $definition): array
    {
        if ($case->state !== 'approved' || ! $case->final_decision_id || $case->current_working_revision_id) {
            $this->invalid('A final Director-approved application revision is required. No waiting-day rule applies.');
        }
        $decision = $case->decisionStages()->whereKey($case->final_decision_id)->first();
        if (! $decision || $decision->action !== 'approved' || $decision->stage_role !== 'director' || $decision->submission_id !== $case->current_submission_id) {
            $this->invalid('The Director approval does not match the current submission.');
        }
        $submission = $case->currentSubmission;
        $module = $case->module;
        if (! $submission || $submission->institution_id !== $case->institution_id || $submission->routing_region_id !== $case->routing_region_id || $submission->module_id !== $case->module_id || ! $module->is_active || ! in_array($module->code, ['registration', 'acsa'], true) || ! Region::whereKey($submission->routing_region_id)->where('is_active', true)->exists()) {
            $this->invalid('The approved module or routing is unavailable.');
        }
        if (! $definition || $definition->state !== 'published' || $definition->module_id !== $case->module_id || $definition->source_form_version_id !== $case->manifest->form_version_id) {
            $this->invalid('OSDS must publish an issuance definition matching this exact application questionnaire.');
        }
        $identity = ['certificate_type' => $module->code === 'registration' ? 'cor' : 'acsa', 'institution_id' => $submission->institution_id, 'approved_institution_name' => $submission->organization_name_snapshot, 'region_id' => $submission->routing_region_id, 'region' => $case->region->name, 'application_id' => $case->id, 'submission_id' => $submission->id, 'submission_hash' => $submission->combined_hash, 'decision_id' => $decision->id, 'director_actor_id' => $decision->actor_id];
        foreach (['organization' => 'name_question_id', 'address' => 'address_question_id'] as $field => $binding) {
            $answer = ApplicationSubmissionAnswer::where('submission_id', $submission->id)->where('form_question_id', $definition->definition[$binding])->where('is_applicable', true)->first()?->value;
            if (! is_string($answer) || trim($answer) === '') {
                $this->invalid('The approved '.$field.' answer cannot be resolved. Ask OSDS to review the binding; free-text replacement is not permitted.');
            }
            $identity[$field] = $answer;
        }
        $identity['evidence_revision_ids'] = DB::table('application_submission_evidence_items')->whereIn('submission_requirement_id', $submission->requirements()->pluck('id'))->orderBy('id')->pluck('evidence_reference_revision_id')->all();

        return $identity;
    }

    public function answers(array $definition, array $answers, bool $complete): array
    {
        $allowed = array_column($definition['questions'], 'key');
        if (array_diff(array_keys($answers), $allowed)) {
            $this->invalid('Unexpected answer fields were rejected. Protected identity cannot be submitted.');
        }
        $clean = [];
        $conditions = app(ConditionalLogic::class);
        foreach ($definition['questions'] as $q) {
            $value = $answers[$q['key']] ?? null;
            $active = $conditions->evaluate($q['condition'], $answers);
            if (! $active) {
                continue;
            }
            $required = $complete && $q['required'];
            $rules = [$required ? 'required' : 'nullable'];
            $rules[] = match ($q['type']) {
                'integer' => 'integer', 'decimal' => 'numeric', 'date' => 'date_format:Y-m-d', 'email' => 'email', 'yes_no' => 'in:yes,no', 'declaration' => $required ? 'accepted' : Rule::in([true, false, 'yes', 'no', 0, 1]), 'multiple_choice' => 'array', default => 'string'
            };
            if (in_array($q['type'], ['short_text', 'long_text', 'telephone', 'email'], true)) {
                $rules[] = 'max:4000';
            }
            $ruleSet = ['answer' => $rules];
            if ($q['type'] === 'single_choice') {
                $ruleSet['answer'][] = Rule::in(array_column($q['options'], 'key'));
            }
            if ($q['type'] === 'multiple_choice') {
                $ruleSet['answer.*'] = [Rule::in(array_column($q['options'], 'key'))];
            }
            $v = validator(['answer' => $value], $ruleSet);
            if ($v->fails()) {
                throw ValidationException::withMessages(['answers.'.$q['key'] => $q['label'].': '.$v->errors()->first()]);
            }
            $clean[$q['key']] = $value;
        }

        return $clean;
    }

    public function persist(User $actor, Application $case, int $definitionId, int $expectedVersion, string $key, array $answers, bool $submit): IssuanceRequest
    {
        validator(['key' => $key], ['key' => 'required|uuid'])->validate();

        return DB::transaction(function () use ($actor, $case, $definitionId, $expectedVersion, $key, $answers, $submit) {
            $actor = $this->actor($actor);
            $case = Application::lockForUpdate()->findOrFail($case->id);
            DB::table('institution_representatives')->where('user_id', $actor->id)->orderBy('id')->lockForUpdate()->get();
            DB::table('applicant_sandbox_eligibility_grants')->where('institution_id', $case->institution_id)->orderBy('id')->lockForUpdate()->get();
            $this->authorizeCase($actor, $case, true);
            $row = IssuanceRequest::where('decision_id', $case->final_decision_id)->where('state', 'submitted')->lockForUpdate()->first();
            // A retry may retrieve its immutable submitted snapshot after fresh case
            // authority even if a later publication replaced the original form.
            if ($row?->state === 'submitted') {
                if ($row->actor_id !== $actor->id || $row->request_key !== $key || $row->definition_id !== $definitionId || $row->payload_hash !== $this->hash($this->answers($row->definition_snapshot, $answers, true))) {
                    $this->invalid('An original request already exists, or this retry has conflicting content. Open the saved request.');
                }

                return $row;
            }
            $row = IssuanceRequest::where('decision_id', $case->final_decision_id)->where('definition_id', $definitionId)->where('actor_id', $actor->id)->where('state', 'draft')->lockForUpdate()->first();
            $definition = IssuanceDefinition::lockForUpdate()->findOrFail($definitionId);
            $identity = $this->eligibility($case, $definition);
            $clean = $this->answers($definition->definition, $answers, $submit);
            if ($submit) {
                $this->requirements($case, $definition->definition, $clean);
            }
            $hash = $this->hash($clean);
            if ($row && ($row->actor_id !== $actor->id || $row->request_key !== $key || $row->definition_id !== $definitionId)) {
                $this->invalid('Resume the existing original draft. A new request cannot replace it.');
            }
            if (($row?->lock_version ?? 0) !== $expectedVersion) {
                $this->invalid('This draft changed. Inspect the saved request before trying again.');
            }
            if (! $row) {
                $row = IssuanceRequest::create(['application_id' => $case->id, 'submission_id' => $case->current_submission_id, 'decision_id' => $case->final_decision_id, 'definition_id' => $definition->id, 'module_id' => $case->module_id, 'institution_id' => $case->institution_id, 'region_id' => $case->routing_region_id, 'actor_id' => $actor->id, 'certificate_type' => $identity['certificate_type'], 'state' => 'draft', 'identity_snapshot' => $identity, 'definition_snapshot' => $definition->definition, 'answers' => $clean, 'request_key' => $key, 'payload_hash' => $hash]);
            } else {
                $row->update(['answers' => $clean, 'payload_hash' => $hash, 'lock_version' => $row->lock_version + 1]);
            }
            if ($submit) {
                $row->update(['state' => 'submitted', 'submitted_at' => now(), 'lock_version' => $row->lock_version + 1]);
            }
            $this->event($actor, $definition, $submit ? 'request_submitted' : 'draft_saved', $row);

            return $row;
        }, 1);
    }

    private function requirements(Application $case, array $definition, array $answers): array
    {
        $ids = [];
        foreach ($definition['requirements'] as $r) {
            if (! app(ConditionalLogic::class)->evaluate($r['condition'], $answers)) {
                continue;
            }
            $evidence = $case->currentSubmission->requirements()->where('is_applicable', true)->whereHas('requirement', fn ($q) => $q->where('key', $r['key']))->first();
            if ((! $evidence && $r['required']) || ($evidence && ($evidence->active_count < ($r['required'] ? $r['minimum'] : 0) || $evidence->active_count > $r['maximum']))) {
                $this->invalid('The approved evidence count does not satisfy '.$r['label'].'. Ask OSDS to review the definition. New document uploads are not authorized by this form.');
            }
            foreach ($evidence?->items ?? [] as $item) {
                $ids[] = $item->evidence_reference_revision_id;
            }
        }

        return $ids;
    }

    private function event(User $actor, IssuanceDefinition $definition, string $event, ?IssuanceRequest $request = null): void
    {
        DB::table('issuance_history')->insert(['definition_id' => $definition->id, 'request_id' => $request?->id, 'actor_id' => $actor->id, 'event' => $event, 'created_at' => now()]);
        app(AuditRecorder::class)->record('issuance.'.$event, 'success', $actor, $request ?? $definition, $request?->region_id, $request?->institution_id, ['definition_id' => $definition->id, 'application_id' => $request?->application_id, 'submission_id' => $request?->submission_id, 'decision_id' => $request?->decision_id]);
    }

    private function hash(array $value): string
    {
        ksort($value);

        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['issuance' => $message]);
    }
}
