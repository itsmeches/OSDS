<section class="intake-panel" aria-labelledby="b1-heading">
    <h2 id="b1-heading">Three-stage demonstration decision</h2>
    <p>Evaluate → Chief recommendation → Director approval/rejection. Three distinct staff identities are required across this case and all its revisions.</p>
    @php($stageHistory = $this->decisionHistory)
    @php($currentStage = $stageHistory->where('submission_id', $selected->current_submission_id)->last())
    <p>Application: {{ $selected->state }} · Current stage: {{ $currentStage?->action ?? 'Awaiting evaluation' }} · Revision ID {{ $selected->current_submission_id }}</p>
    @error('decision')<p role="alert">{{ $message }}</p>@enderror
    @if($selected->state === 'under_screening' && ($copy->id === $selected->current_submission_id) && collect(\App\Services\Applications\ApplicationDecisionService::PERMISSIONS)->contains(fn ($permission) => auth()->user()->can($permission)))
        <div class="intake-form-grid">
            <label class="intake-field intake-field-wide"><span>Applicant-facing stage summary (required)</span><textarea wire:model="decisionSummary" maxlength="2000" rows="3"></textarea>@error('summary')<em role="alert">{{ $message }}</em>@enderror</label>
            <label class="intake-field intake-field-wide"><span>Internal review notes (staff only)</span><textarea wire:model="decisionNotes" maxlength="4000" rows="3"></textarea>@error('internal_notes')<em role="alert">{{ $message }}</em>@enderror</label>
            @if(auth()->user()->can('applications.evaluation.record') && (!$currentStage || $currentStage->action === 'returned'))
                <label class="intake-field intake-field-wide"><span><input type="checkbox" wire:model="decisionAttested"> I reviewed this exact submission's answers and applicable published synthetic checklist. This is not a finding of legal compliance.</span></label>
                <button type="button" class="intake-primary-action" wire:click="recordDecisionStage('evaluated')" wire:loading.attr="disabled">Record technical evaluation</button>
            @endif
            @if(auth()->user()->can('applications.recommendations.record') && $currentStage?->action === 'evaluated')
                <button type="button" class="intake-primary-action" wire:click="recordDecisionStage('recommended')" wire:loading.attr="disabled">Recommend approval</button>
                <button type="button" class="secondary" wire:click="recordDecisionStage('returned')" wire:loading.attr="disabled">Return for reevaluation</button>
            @endif
            @if(auth()->user()->can('applications.decisions.record') && $currentStage?->action === 'recommended')
                <button type="button" class="intake-primary-action" wire:click="recordDecisionStage('approved')" wire:loading.attr="disabled" wire:confirm="Approve this exact revision for the synthetic demonstration?">Approve demonstration application</button>
                <button type="button" class="secondary" wire:click="recordDecisionStage('rejected')" wire:loading.attr="disabled" wire:confirm="Reject this exact revision for the synthetic demonstration?">Reject demonstration application</button>
            @endif
        </div>
    @endif
    <details class="intake-history"><summary>Decision-stage history (staff notes)</summary><ol>@foreach($stageHistory as $stage)<li><strong>{{ $stage->stage_role }} · {{ $stage->action }} · Submission {{ $stage->submission_id }} · Actor {{ $stage->actor_id }}</strong><p>{{ $stage->applicant_summary }}</p>@if($stage->internal_notes)<p>Internal: {{ $stage->internal_notes }}</p>@endif</li>@endforeach</ol></details>
</section>
