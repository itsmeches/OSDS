<?php

namespace App\Services\Applications;

use App\Models\Application;
use App\Models\ApplicationDecisionStage;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationDecisionService
{
    public const PERMISSIONS = [
        'evaluator' => 'applications.evaluation.record',
        'chief' => 'applications.recommendations.record',
        'director' => 'applications.decisions.record',
    ];

    private const ROLES = ['evaluated' => 'evaluator', 'correction_requested' => 'evaluator', 'recommended' => 'chief', 'returned' => 'chief', 'approved' => 'director', 'rejected' => 'director'];

    private const EVENTS = ['evaluated' => 'application.evaluation_recorded', 'correction_requested' => 'application.evaluation_corrections_requested', 'recommended' => 'application.recommendation_recorded', 'returned' => 'application.reevaluation_requested', 'approved' => 'application.approved', 'rejected' => 'application.rejected'];

    public function act(User $actor, Application $case, int $submissionId, int $expectedVersion, ?int $expectedStage, string $requestKey, string $action, array $input): ApplicationDecisionStage
    {
        $data = validator(['action' => $action, 'request_key' => $requestKey] + $input, [
            'action' => 'required|in:'.implode(',', array_keys(self::ROLES)), 'request_key' => 'required|uuid',
            'summary' => 'required|string|max:2000', 'internal_notes' => 'nullable|string|max:4000',
            'attested' => 'sometimes|boolean', 'items' => 'sometimes|array',
        ])->validate();
        $role = self::ROLES[$action];
        if ($action === 'evaluated' && ! ($data['attested'] ?? false)) {
            $this->invalid('Attest review of this exact submitted questionnaire and applicable checklist.');
        }
        $hash = hash('sha256', json_encode([$submissionId, $expectedStage, $action, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $case, $submissionId, $expectedVersion, $expectedStage, $requestKey, $action, $data, $role, $hash) {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
            $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
            SyntheticApplicationReviewerAssignment::where('reviewer_id', $actor->id)->orderBy('id')->lockForUpdate()->get();
            $case = Application::query()->lockForUpdate()->findOrFail($case->id);
            $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();
            $lifecycle = app(ApplicationLifecycleService::class);
            if (auth()->id() !== $actor->id || $actor->id === $case->applicant_id || ! $actor->can('platform.admin.access') || ! $actor->can('applications.intake.view')) {
                throw new AuthorizationException;
            }
            $lifecycle->authorizeReviewer($actor, $case, self::PERMISSIONS[$role]);
            if ($case->current_submission_id !== $submissionId) {
                $this->invalid('The submitted revision changed. Reload the case.');
            }
            $prior = ApplicationDecisionStage::where(['application_id' => $case->id, 'actor_id' => $actor->id, 'request_key' => $requestKey])->first();
            if ($prior) {
                if (! hash_equals($prior->payload_hash, $hash)) {
                    $this->invalid('This request key already belongs to a different stage action.');
                }

                return $prior;
            }
            if ($case->state !== 'under_screening' || $case->current_working_revision_id !== null || $case->lock_version !== $expectedVersion) {
                $this->invalid('The case changed or is not under screening. Reload before acting.');
            }
            if ($case->decisionStages()->where('actor_id', $actor->id)->where('stage_role', '<>', $role)->exists()) {
                throw new AuthorizationException('Three distinct staff identities are required across this entire case.');
            }
            $latest = $case->decisionStages()->where('submission_id', $submissionId)->reorder('id', 'desc')->first();
            if ($latest?->id !== $expectedStage) {
                $this->invalid('The decision stage changed. Reload the case.');
            }
            $allowed = match ($role) {
                'evaluator' => $action === 'correction_requested' || ! $latest || $latest->action === 'returned',
                'chief' => $latest?->action === 'evaluated',
                'director' => $latest?->action === 'recommended',
            };
            if (! $allowed) {
                $this->invalid('The required evaluation or recommendation is missing or superseded.');
            }
            $correction = null;
            if ($action === 'correction_requested') {
                // Existing service alone owns correction records, working copy,
                // state change and its single history row. Outer B1 transaction
                // owns the additional stage/audit and rolls everything back.
                $correction = $lifecycle->requestCorrection($actor, $case, $data['summary'], $data['items'] ?? []);
            }
            $stage = ApplicationDecisionStage::create([
                'application_id' => $case->id, 'submission_id' => $submissionId, 'actor_id' => $actor->id,
                'stage_role' => $role, 'action' => $action, 'predecessor_id' => $expectedStage,
                'correction_request_id' => $correction?->id, 'attested' => (bool) ($data['attested'] ?? false),
                'applicant_summary' => $data['summary'], 'internal_notes' => $data['internal_notes'] ?? null,
                'request_key' => $requestKey, 'payload_hash' => $hash,
            ]);
            if (! $correction) {
                $to = $role === 'director' ? $action : 'under_screening';
                $case->update(['state' => $to, 'lock_version' => $case->lock_version + 1, 'final_decision_id' => $role === 'director' ? $stage->id : null]);
                DB::table('application_state_history')->insert([
                    'application_id' => $case->id, 'from_state' => 'under_screening', 'to_state' => $to,
                    'event' => self::EVENTS[$action], 'submission_id' => $submissionId, 'actor_id' => $actor->id,
                    'applicant_summary' => $data['summary'], 'correlation_id' => (string) Str::uuid(), 'created_at' => now(),
                ]);
            }
            app(AuditRecorder::class)->record(self::EVENTS[$action], 'success', $actor, $stage, $case->routing_region_id, $case->institution_id,
                ['application_id' => $case->id, 'submission_id' => $submissionId, 'predecessor_id' => $expectedStage]);
            if ($role === 'director') {
                DB::statement('SET CONSTRAINTS b1_final_commit IMMEDIATE');
                DB::statement('SET CONSTRAINTS b1_final_commit DEFERRED');
            }

            return $stage;
        }, 1);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['decision' => $message]);
    }
}
