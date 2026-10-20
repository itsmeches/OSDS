<x-filament-panels::page>
    <section class="osds-workflow-card">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="osds-muted text-sm font-medium">{{ $review->module->name }}</p>
                <h2 class="mt-1 text-xl font-semibold">Submitted configuration</h2>
                <p class="osds-muted mt-1 text-sm">Submitted {{ $review->submitted_at->timezone('Asia/Manila')->format('M j, Y g:i A') }} by {{ $review->submittedBy?->name ?? 'Unknown user' }}</p>
            </div>
            <x-filament::badge :color="\App\Support\QuestionnairePresentation::statusColor($review->state)" :icon="\App\Support\QuestionnairePresentation::statusIcon($review->state)">
                {{ \App\Support\QuestionnairePresentation::statusLabel($review->state) }}
            </x-filament::badge>
        </div>
        <div class="notice mt-5 text-sm">
            @if($review->state === 'rejected')
                This configuration was returned for changes. Its versions are editable again; current draft content is not an unchanged copy of this review. The recorded decision and references remain below.
            @else
                Review both sections below. Your decision applies to this exact questionnaire and document pair. Approval alone does not publish it to applicants.
            @endif
        </div>
    </section>

    @if($review->state !== 'rejected')
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="osds-workflow-card" aria-labelledby="review-questions-heading">
            <h2 id="review-questions-heading" class="text-lg font-semibold">Questionnaire v{{ $review->formVersion->version_number }}</h2>
            <p class="osds-muted mt-1 text-sm">{{ $review->formVersion->definition->name }}</p>
            <div class="mt-5 space-y-4">
                @foreach ($review->formVersion->sections as $section)
                    <article class="rounded-lg border border-gray-200 p-4">
                        <h3 class="font-semibold">{{ $section->title }}</h3>
                        @if($section->description)<p class="osds-muted mt-1 text-sm">{{ $section->description }}</p>@endif
                        <ol class="mt-3 space-y-3">
                            @foreach ($section->questions as $question)
                                <li class="text-sm">
                                    <div class="font-medium">{{ $question->label }} @if($question->is_required)<span class="questionnaire-required">(required)</span>@endif</div>
                                    @if($question->help_text)<p class="osds-muted">{{ $question->help_text }}</p>@endif
                                    <div class="osds-muted">{{ str($question->type)->replace('_', ' ')->title() }}@if($question->options->isNotEmpty()) &middot; {{ $question->options->pluck('label')->join(', ') }}@endif</div>
                                    @if($question->condition_rules)<details class="mt-2"><summary>When this question is shown</summary>@include('filament.pages.partials.builder-condition-summary', ['rules' => $question->condition_rules, 'sections' => $conditionSections])</details>@endif
                                    @if($question->validation_rules)<details class="mt-2"><summary>Answer checks — support details</summary><pre class="osds-technical-details whitespace-pre-wrap break-all">{{ json_encode($question->validation_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="osds-workflow-card" aria-labelledby="review-documents-heading">
            <h2 id="review-documents-heading" class="text-lg font-semibold">Document requirements v{{ $review->requirementSetVersion->version_number }}</h2>
            <p class="osds-muted mt-1 text-sm">Verify instructions, required status, accepted link types, and evidence counts.</p>
            <div class="mt-5 space-y-3">
                @foreach ($review->requirementSetVersion->requirements as $requirement)
                    <article class="rounded-lg border border-gray-200 p-4">
                        <h3 class="font-medium">{{ $requirement->label }} @if($requirement->is_required)<span class="questionnaire-required">(required)</span>@endif</h3>
                        @if($requirement->instructions)<p class="osds-muted mt-1 text-sm">{{ $requirement->instructions }}</p>@endif
                        <p class="mt-2 text-sm">Documents: {{ $requirement->minimum_evidence }}&ndash;{{ $requirement->maximum_evidence }}</p>
                        <p class="osds-muted text-sm">Accepted links: {{ $requirement->providers->pluck('name')->join(', ') }}</p>
                        @if($requirement->condition_rules)<details class="mt-2 text-sm"><summary>When this document is needed</summary>@include('filament.pages.partials.builder-condition-summary', ['rules' => $requirement->condition_rules, 'sections' => $conditionSections])</details>@endif
                    </article>
                @endforeach
            </div>
        </section>
    </div>
    @endif

    @if($review->approvals->isNotEmpty())
    <section class="osds-workflow-card mt-6" aria-labelledby="review-history-heading">
        <h2 id="review-history-heading" class="text-lg font-semibold">Recorded review</h2>
        @foreach($review->approvals as $decision)
            <p class="mt-2">{{ $decision->outcome === 'approved' ? 'Configuration approved' : 'Changes requested' }} · {{ $decision->approval_reference }}</p>
            @if($decision->comment)<p class="osds-muted mt-2 whitespace-pre-line">{{ $decision->comment }}</p>@endif
        @endforeach
    </section>
    @endif

    <details class="osds-workflow-card mt-6">
        <summary class="cursor-pointer font-semibold">Technical details for authorized support</summary>
        <dl class="osds-technical-details grid gap-3 text-sm md:grid-cols-2">
            <div><dt class="font-medium">Review ID</dt><dd class="osds-muted">{{ $review->public_id }}</dd></div>
            <div><dt class="font-medium">Configuration reference</dt><dd class="osds-muted font-mono">{{ $review->combined_hash }}</dd></div>
            <div><dt class="font-medium">Questionnaire reference</dt><dd class="osds-muted font-mono">{{ $review->form_hash }}</dd></div>
            <div><dt class="font-medium">Document reference</dt><dd class="osds-muted font-mono">{{ $review->requirement_hash }}</dd></div>
        </dl>
    </details>

    <section class="osds-workflow-card mt-6">
        <h2 class="text-lg font-semibold">What happens next?</h2>
        @if($review->state === 'pending')
            <p class="osds-muted mt-2 text-sm">Approve the exact configuration or request changes using the actions at the top of the page.</p>
        @elseif($review->state === 'approved')
            <p class="osds-muted mt-2 text-sm">Choose a publication audience using the actions above: staff preview or synthetic applicants. Only the applicant publication can appear in Start application, and the organization must also have current eligibility.</p>
        @else
            <p class="osds-muted mt-2 text-sm">OSDS can correct the returned drafts and send the exact pair for a new review. This recorded decision is retained.</p>
        @endif
    </section>
</x-filament-panels::page>
