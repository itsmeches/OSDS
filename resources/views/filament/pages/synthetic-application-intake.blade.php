<x-filament-panels::page>
    <div class="intake-workspace">
        <x-reviewer-feedback />
        <div class="intake-banner" role="status">
            <strong>Test review only</strong>
            <span>This workspace cannot approve, reject, certify, or issue an application.</span>
        </div>

        @php($selected = $this->selected)

        @if (! $selected)
            <header class="intake-page-heading">
                <div><p class="intake-eyebrow">CHEDRO workspace</p><h1>Assigned applications</h1><p>Open a synthetic application assigned to your region and service.</p></div>
            </header>
            <x-reviewer-queue :records="$this->applications" :options="$this->queueOptions" />
        @else
            @php($copy = $this->submittedCopy)
            <a class="intake-back" href="{{ static::getUrl() }}">&larr; Assigned applications</a>
            <header class="intake-page-heading">
                <div><p class="intake-eyebrow">{{ $selected->manifest->module->name }}</p><h1>{{ $selected->institution->display_name ?: $selected->institution->name }}</h1><div class="intake-meta"><span class="intake-status">{{ match($selected->state) { 'submitted'=>'Ready for screening', 'resubmitted'=>'Corrections received', 'under_screening'=>'Screening in progress', 'correction_requested'=>'Waiting for applicant', default=>'View application' } }}</span><span>Submitted test application</span></div></div>
                @if (in_array($selected->state, ['submitted', 'resubmitted'], true) && auth()->user()->can('applications.screening.start'))
                    <button class="intake-primary-action" wire:click="beginScreening" wire:loading.attr="disabled" wire:target="beginScreening" wire:confirm="Start screening this exact synthetic submission?"><span wire:loading.remove wire:target="beginScreening">Start screening</span><span wire:loading wire:target="beginScreening">Starting&hellip;</span></button>
                @endif
            </header>

            @error('application')<div class="intake-error" role="alert"><strong>Action not completed</strong><span>{{ $message }}</span></div>@enderror

            <section class="intake-panel">
                <label class="intake-field"><span>Submitted copy</span><select aria-label="Submitted copy" wire:model.live="submission"><option value="">Latest submitted copy</option>@foreach($selected->submissions as $version)<option value="{{ $version->public_id }}">Sent {{ $version->submitted_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}{{ $version->id === $selected->current_submission_id ? ' — latest' : ' — earlier copy' }}</option>@endforeach</select></label>
                <p>{{ $copy->id === $selected->current_submission_id ? 'Reviewing the latest submitted answers and documents.' : 'Read-only earlier submission. Correction requests always apply to the latest submission.' }} Earlier copies never change when an applicant edits a correction.</p>
            </section>
            <nav class="intake-flow" aria-label="Screening workflow">
                <span class="is-complete"><b>1</b> Open case</span><span @class(['is-current' => $selected->state === 'under_screening'])><b>2</b> Review submission</span><span><b>3</b> Request corrections if needed</span>
            </nav>

            @if (auth()->user()->can('applications.intake.answers.view'))
                <section class="intake-panel">
                    <div class="intake-panel-heading"><div><p class="intake-eyebrow">Step 1</p><h2>Questionnaire answers</h2><p>Answers captured in this immutable submission.</p></div><span class="intake-count">{{ $copy->answers->where('is_applicable', true)->count() }}</span></div>
                    <dl class="intake-answer-grid">
                        @foreach ($copy->answers->where('is_applicable', true) as $answer)
                            <div><dt>{{ $answer->question->label }}</dt><dd><x-questionnaires.answer :question="$answer->question" :value="$answer->value" /></dd></div>
                        @endforeach
                    </dl>
                </section>
            @endif

            <section class="intake-panel">
                <div class="intake-panel-heading"><div><p class="intake-eyebrow">Step 2</p><h2>Required documents</h2><p>Open each submitted reference in a new tab and confirm it matches the listed requirement.</p></div></div>
                <div class="intake-document-list">
                    @foreach ($copy->requirements->where('is_applicable', true) as $requirement)
                        <article class="intake-document-card">
                            <div><h3>{{ $requirement->requirement->label }}</h3><p>{{ $requirement->active_count }} {{ str('document')->plural($requirement->active_count) }} submitted</p></div>
                            <div class="intake-document-actions">
                                @if(auth()->user()->can('applications.intake.evidence.open'))
                                @forelse ($requirement->items as $index => $item)
                                    @if($copy->id === $selected->current_submission_id)
                                    <a target="_blank" rel="noopener noreferrer" href="{{ route('application.evidence.open', [$selected, $item->revision]) }}">Open document{{ $requirement->items->count() > 1 ? ' '.($index + 1) : '' }} <span aria-hidden="true">&nearr;</span></a>
                                    @else
                                    <span>Document {{ $index + 1 }} preserved in this earlier copy. Opening is available only for the latest submitted copy.</span>
                                    @endif
                                @empty
                                    <span class="intake-missing">No document supplied</span>
                                @endforelse
                                @else<span>Document opening requires additional permission.</span>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @if ($selected->state === 'under_screening' && $copy->id === $selected->current_submission_id && auth()->user()->can('applications.corrections.request'))
                <section class="intake-panel intake-correction-panel">
                    <div class="intake-panel-heading"><div><p class="intake-eyebrow">Step 3</p><h2>Request a correction</h2><p>Describe one specific change in plain language. The submitted copy remains unchanged.</p></div></div>
                    <div class="intake-form">
                        @if($errors->any())<div class="intake-error intake-field-wide" role="alert"><div><strong>Check the correction request</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
                        <label class="intake-field intake-field-wide"><span>Applicant summary</span><small>Briefly explain why a correction is needed.</small><textarea wire:model="correctionSummary" maxlength="2000" rows="3"></textarea>@error('summary')<em role="alert">{{ $message }}</em>@enderror</label>
                        <label class="intake-field"><span>Correction applies to</span><select aria-label="Correction applies to" wire:model.live="correctionTarget"><option value="general">The application generally</option>@if(auth()->user()->can('applications.intake.answers.view'))<option value="question">A questionnaire answer</option>@endif<option value="document_requirement">A required document</option></select></label>
                        @if ($correctionTarget === 'question' && auth()->user()->can('applications.intake.answers.view'))
                            <label class="intake-field"><span>Question</span><select aria-label="Question" wire:model="correctionQuestion"><option value="">Select a question</option>@foreach($copy->answers as $answer)<option value="{{ $answer->form_question_id }}">{{ $answer->question->label }}</option>@endforeach</select></label>
                        @elseif ($correctionTarget === 'document_requirement')
                            <label class="intake-field"><span>Document requirement</span><select aria-label="Document requirement" wire:model="correctionRequirement"><option value="">Select a document</option>@foreach($copy->requirements as $requirement)<option value="{{ $requirement->documentary_requirement_id }}">{{ $requirement->requirement->label }}</option>@endforeach</select></label>
                        @endif
                        <label class="intake-field"><span>Issue category</span><select aria-label="Issue category" wire:model="correctionCategory"><option value="clarification">Needs clarification</option><option value="incomplete">Incomplete information</option><option value="document_mismatch">Document does not match</option><option value="inaccessible">Document cannot be opened</option></select></label>
                        <label class="intake-field intake-field-wide"><span>Instruction for the applicant</span><small>State exactly what the applicant should change.</small><textarea wire:model="correctionInstruction" maxlength="2000" rows="4"></textarea>@error('items')<em role="alert">{{ $message }}</em>@enderror</label>
                        <div class="intake-form-actions"><button class="intake-primary-action" wire:click="requestCorrection" wire:loading.attr="disabled" wire:target="requestCorrection" wire:confirm="Request this correction and open a new applicant working revision?"><span wire:loading.remove wire:target="requestCorrection">Send correction request</span><span wire:loading wire:target="requestCorrection">Sending&hellip;</span></button></div>
                    </div>
                </section>
            @elseif ($selected->state === 'correction_requested')
                <div class="intake-info" role="status"><strong>Waiting for the applicant</strong><span>A correction request is active. The applicant can prepare and resubmit a new working revision.</span></div>
            @endif

            @if(auth()->user()->can('applications.corrections.request') && $selected->correctionRequests->isNotEmpty())
                <details class="intake-panel intake-history"><summary>Correction instructions sent</summary><ol>@foreach($selected->correctionRequests as $request)<li><div><strong>{{ $request->applicant_summary }}</strong><ul>@foreach($request->items as $item)<li>{{ $item->instruction }}</li>@endforeach</ul></div><time>{{ $request->requested_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time></li>@endforeach</ol></details>
            @endif
            <details class="intake-panel intake-history"><summary>Status history <span>{{ $selected->history->count() }} events</span></summary><ol>@foreach($selected->history as $event)<li><span>{{ $event->applicant_summary }}</span><time>{{ $event->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time></li>@endforeach</ol></details>
        @endif
    </div>
</x-filament-panels::page>
