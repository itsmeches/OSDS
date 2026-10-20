@php
    $sections = $draft->manifest->formVersion->sections;
    $documentsStep = $sections->count();
    $reviewStep = $documentsStep + 1;
    $requirements = $draft->manifest->requirementSetVersion->requirements;
    $applicableRequirements = $requirements->filter(fn ($requirement) => $this->requirementApplicable($requirement));
    $completeRequirements = $applicableRequirements->filter(fn ($requirement) => count($this->evidenceFor($requirement->id)) >= $requirement->minimum_evidence)->count();
@endphp

<div class="application-editor" data-applicant-editor data-question-labels="{{ json_encode($sections->flatMap->questions->pluck('label', 'id')) }}">
    <section data-recovery wire:ignore hidden class="field-error" role="alert">
        <h2>Action not confirmed</h2><p data-recovery-message></p>
        <div class="inline-actions"><button type="button" class="secondary" data-retry>Retry same action</button><button type="button" class="secondary" data-copy-local>Show unsaved answers to copy</button><a href="{{ route('applicant.login') }}" target="_blank" rel="noopener noreferrer">Sign in in a new tab</a></div>
    </section>
    <label class="local-answer-copy" hidden data-copy-label>Unsaved answers — copy before reloading<textarea data-local-copy hidden readonly aria-label="Unsaved answers to copy" rows="8"></textarea></label>
    <header class="application-header">
        <div>
            <a class="application-back-link" href="{{ route('applicant.home') }}">&larr; My applications</a>
            <p class="eyebrow">{{ $draft->module->name }}</p>
            <h1>{{ $draft->institution->display_name ?: $draft->institution->name }}</h1>
            <p>Complete the questionnaire, add document links, then review and send your test application.</p>
        </div>
        <div @class(['save-state', 'server-save-state', 'save-state-error' => in_array($saveState, ['Save failed', 'Updated in another tab'], true)]) aria-live="polite">
            <span aria-hidden="true">{{ $saveState === 'Saved' ? '✓' : ($saveState === 'Saving' ? '…' : '•') }}</span>
            {{ $saveState }}
        </div>
        <p class="save-state" data-client-save-state wire:ignore hidden role="status"></p>
    </header>

    @if ($this->isReadOnly())
        <div class="notice" role="status"><strong>Read-only application</strong><p>This questionnaire version or working revision is closed, so editing is unavailable. Your saved answers and documents remain available.</p></div>
    @endif

    @if($correction)
        <section class="portal-card correction-instructions"><h2>Changes requested by CHEDRO</h2><p>{{ $correction->applicant_summary }}</p><ol>@foreach($correction->items as $item)<li>{{ $item->instruction }}</li>@endforeach</ol><p class="field-help">Update this working copy, save, then review and send your corrections. The earlier copy stays unchanged.</p></section>
    @endif
    @if($saveState === 'Updated in another tab')
        <div class="field-error" role="alert"><strong>Your input has not replaced the newer saved copy.</strong><p>Copy any changes you need before reloading. Reloading replaces this tab with the saved version.</p><button class="secondary" type="button" data-copy-local>Show unsaved answers to copy</button> <a href="{{ route('applicant.drafts.edit', $draft) }}" onclick="return confirm('Reload the saved copy? Unsaved input in this tab will be replaced.');">Reload saved copy</a></div>
    @endif

    <div class="draft-workspace" data-editor-workspace>
        <aside class="progress-rail" aria-label="Application steps">
            <div class="progress-heading"><strong>Step {{ $section + 1 }} of {{ $reviewStep + 1 }}</strong><span>{{ $section < $documentsStep ? $sections[$section]->title : ($section === $documentsStep ? 'Documents' : 'Review and send') }}</span><button type="button" data-steps-toggle aria-controls="applicant-steps" aria-expanded="false">All steps</button></div>
            <nav id="applicant-steps" class="applicant-step-list" aria-label="Go to application step">
            @foreach ($sections as $index => $formSection)
                <button type="button" wire:click="goToStep({{ $index }})" @class(['active' => $section === $index]) @if($section === $index) aria-current="step" @endif>
                    <span>{{ $index + 1 }}</span><span>{{ $formSection->title }}<small>Questionnaire</small></span>
                </button>
            @endforeach
            <button type="button" wire:click="goToStep({{ $documentsStep }})" @class(['active' => $section === $documentsStep]) @if($section === $documentsStep) aria-current="step" @endif>
                <span>{{ $documentsStep + 1 }}</span><span>Documents<small>{{ $completeRequirements }} of {{ $applicableRequirements->count() }} complete</small></span>
            </button>
            <button type="button" wire:click="goToStep({{ $reviewStep }})" @class(['active' => $section === $reviewStep]) @if($section === $reviewStep) aria-current="step" @endif>
                <span>{{ $reviewStep + 1 }}</span><span>Review<small>Check and submit</small></span>
            </button>
            </nav>
        </aside>

        <section class="portal-card questionnaire-card">
            @if ($section < $documentsStep)
                @php($current = $sections[$section])
                <div class="step-heading"><div><p class="eyebrow">Step {{ $section + 1 }}</p><h2>{{ $current->title }}</h2><p>{{ $current->description }}</p></div><span class="status">{{ $current->questions->count() }} questions</span></div>
                <div class="question-list">
                    @foreach ($current->questions as $question)
                        @if ($this->visible($question))
                            <div class="question-card" wire:key="applicant-question-{{ $question->id }}" data-question-id="{{ $question->id }}"><x-questionnaires.field :question="$question" model="answers.{{ $question->id }}" :disabled="$this->isReadOnly()" /></div>
                        @endif
                    @endforeach
                </div>
            @elseif ($section === $documentsStep)
                <div class="step-heading"><div><p class="eyebrow">Step {{ $documentsStep + 1 }}</p><h2>Document links</h2><p>Add a link to each applicable Google file. Files remain in Google; the platform stores only an encrypted reference.</p></div><span class="status">{{ $completeRequirements }}/{{ $applicableRequirements->count() }} complete</span></div>
                <div class="document-guidance"><strong>How to add a document</strong><span>Open the individual Google file, copy its full browser address, paste it below and choose Add link. Each confirmed link is saved immediately. A valid link does not prove CHEDRO can access the file.</span><code>https://docs.google.com/document/d/FILE_ID/edit</code></div>
                @if($saveState !== 'Saved')<p class="notice" role="status">Save your questionnaire answers before adding links. This keeps document requirements in sync with your answers.</p>@endif

                <div class="document-list">
                    @foreach ($requirements as $requirement)
                        @php($applicable = $this->requirementApplicable($requirement))
                        @php($references = $this->evidenceFor($requirement->id))
                        @php($complete = $applicable && count($references) >= $requirement->minimum_evidence)
                        <article wire:key="applicant-document-{{ $requirement->id }}" @class(['document-card', 'document-card-complete' => $complete, 'document-card-inactive' => ! $applicable])>
                            <div class="document-card-heading">
                                <div><div class="document-title-row"><h3>{{ $requirement->label }}</h3><span @class(['requirement-badge', 'required' => $requirement->is_required, 'complete' => $complete])>{{ ! $applicable ? 'Not applicable' : ($complete ? 'Complete' : ($requirement->is_required ? 'Required' : 'Optional')) }}</span></div><p>{{ $requirement->instructions }}</p></div>
                                @if ($applicable)<strong class="document-count">{{ count($references) }} added · {{ $requirement->minimum_evidence }}–{{ $requirement->maximum_evidence }} allowed</strong>@endif
                            </div>

                            @if ($applicable)
                                <p class="field-help">Accepted for this document: {{ $requirement->providers->pluck('name')->join(', ') }}.</p>
                                @if (count($references))
                                    <ul class="evidence-list">
                                        @foreach ($references as $reference)
                                            <li><span><strong>{{ $reference->latestRevision->provider->name }}</strong><small>Added {{ $reference->latestRevision->created_at->diffForHumans() }}</small></span><span class="evidence-actions"><a href="{{ route('applicant.evidence.open', $reference) }}" target="_blank" rel="noreferrer noopener">Open</a><button type="button" wire:click="removeEvidence('{{ $reference->public_id }}')" wire:confirm="Remove this document link from the current draft?" @disabled($this->isReadOnly())>Remove</button></span></li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (! $this->isReadOnly())
                                    <div class="evidence-entry">
                                        <label for="evidence-{{ $requirement->id }}">{{ count($references) ? 'New Google file link' : 'Google file link' }}</label>
                                        <div class="evidence-input-row"><input id="evidence-{{ $requirement->id }}" type="url" wire:model.live.debounce.400ms="evidenceInputs.{{ $requirement->id }}" placeholder="Paste the complete individual Google file link" autocomplete="off" aria-describedby="evidence-help-{{ $requirement->id }} evidence-error-{{ $requirement->id }}">@if(count($references) === 1 && $requirement->maximum_evidence === 1)<button type="button" class="primary" wire:click="replaceEvidence('{{ $references[0]->public_id }}')" wire:loading.attr="disabled">Replace link</button>@else<button type="button" class="primary" wire:click="addEvidence({{ $requirement->id }})" wire:loading.attr="disabled">Add link</button>@endif</div>
                                        <p id="evidence-help-{{ $requirement->id }}" class="field-help">The link must identify one file. Folder and Google Form links are not accepted.</p>
                                        @if ($evidenceErrors[$requirement->id] ?? null)<p id="evidence-error-{{ $requirement->id }}" class="field-error" role="alert"><strong>Link not added.</strong> {{ $evidenceErrors[$requirement->id] }}</p>@endif
                                        @if ($evidenceStates[$requirement->id] ?? null)<p @class(['field-state', 'success' => in_array($evidenceStates[$requirement->id], ['Link added', 'Link replaced', 'Link removed'], true)]) aria-live="polite">{{ $evidenceStates[$requirement->id] }}</p>@endif
                                    </div>
                                @endif
                            @elseif (count($references))
                                <p class="field-help">Earlier evidence is preserved in history and excluded from current completeness and new test-review copies.</p>
                            @endif
                        </article>
                    @endforeach
                </div>

                <details class="test-review-panel"><summary>Optional: send documents for a separate test review</summary><p>This creates a nonofficial evidence-review copy. It does not submit your application.</p><div class="inline-actions"><button type="button" class="secondary" wire:click="sendDocumentsForTestReview" wire:loading.attr="disabled" @disabled($this->isReadOnly())>Send documents for test review</button>@if($latestReviewCaseId)<button type="button" class="secondary" wire:click="supersedeTestReview" wire:loading.attr="disabled" wire:confirm="Send a newer document test-review copy? Earlier copies will be preserved." @disabled($this->isReadOnly())>Send a newer copy</button>@endif</div>@if($reviewCopyState)<p class="field-state success" role="status">{{ $reviewCopyState }}</p>@endif @error('review')<p class="field-error" role="alert">{{ $message }}</p>@enderror</details>
            @else
                <div class="step-heading"><div><p class="eyebrow">Final step</p><h2>{{ $correction ? 'Review your corrections' : 'Review your test application' }}</h2><p>Save your answers and add all required links, then check for missing information. Sending makes a read-only test copy for CHEDRO.</p></div></div>
                <div class="review-summary"><article><strong>{{ $sections->flatMap->questions->count() }}</strong><span>Questionnaire fields</span></article><article><strong>{{ $completeRequirements }}/{{ $applicableRequirements->count() }}</strong><span>Documents complete</span></article><article><strong>{{ $saveState }}</strong><span>Draft status</span></article></div>
                <details class="review-answer-list"><summary>Review saved questionnaire answers</summary><dl>
                    @foreach($sections->flatMap->questions as $question)
                        @if($this->visible($question))
                            @php($answer = $draft->answers->firstWhere('form_question_id', $question->id)?->value)
                            <div><dt>{{ $question->label }}</dt><dd><x-questionnaires.answer :question="$question" :value="$answer" /></dd></div>
                        @endif
                    @endforeach
                </dl></details>
                <div class="notice"><strong>Synthetic test only</strong><p>Submitting creates an immutable test copy for CHEDRO sandbox screening. It creates no official application, decision, or tracking number.</p></div>
                @error('submission')<p class="field-error" role="alert"><strong>Application not submitted.</strong> {{ $message }}</p>@enderror
                @if($saveState !== 'Saved')<p class="notice">Your questionnaire has unsaved changes. Choose Save draft before sending.</p>@endif
                <div class="review-actions"><button type="button" class="secondary" wire:click="checkProgress" wire:loading.attr="disabled" @disabled($this->isReadOnly())>Check for missing information</button><button type="button" class="primary" wire:click="submitTestApplication" wire:loading.attr="disabled" wire:confirm="Send this test copy to CHEDRO? You can edit it again only if corrections are requested." @disabled($this->isReadOnly() || $saveState !== 'Saved')>{{ $correction ? 'Send corrected test application' : 'Submit test application' }}</button></div>
            @endif

            @error('save')<p class="field-error" role="alert"><strong>Draft not saved.</strong> {{ $message }}</p>@enderror
            @error('progress')<p class="field-error" role="alert"><strong>More information is needed.</strong> {{ $message }}</p>@enderror
            @if ($missing || $documentMissing)
                <div class="progress-errors" role="alert"><strong>Complete these items before submitting</strong><ul>@foreach($missing as $item)<li>Answer: {{ $item }}</li>@endforeach @foreach($documentMissing as $item)<li>Document: {{ $item }}</li>@endforeach</ul></div>
            @endif

            <footer class="editor-actions">
                <button type="button" wire:click="goToStep({{ $section - 1 }})" class="secondary" @disabled($section === 0)>Back</button>
                <div>@if(!$this->isReadOnly())<button type="button" wire:click="save" wire:loading.attr="disabled" class="secondary">Save draft</button>@endif @if($section < $reviewStep)<button type="button" wire:click="goToStep({{ $section + 1 }})" class="primary">{{ $section + 1 === $documentsStep ? 'Next: documents' : ($section + 1 === $reviewStep ? 'Next: review' : 'Next step') }}</button>@endif</div>
            </footer>
        </section>
    </div>
</div>
