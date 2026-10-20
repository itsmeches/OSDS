<x-filament-panels::page>
<div class="intake-workspace">
    <x-reviewer-feedback />
    @if($findingConfirmation)<div data-finding-confirmed class="intake-info" role="status">{{ $findingConfirmation }}</div>@endif
    <div class="intake-banner" role="status"><strong>Test review only — not an official application decision</strong><span>Record document checks here. Application screening is a separate workspace.</span></div>
    @php($selected = $this->selected)
    @if(! $selected)
        <header class="intake-page-heading"><div><h1>Assigned document test reviews</h1><p>Find an assigned test copy, inspect its documents, and record your findings.</p></div></header>
        <x-reviewer-queue :records="$this->cases" :options="$this->queueOptions" evidence />
    @else
        <a class="intake-back" href="{{ static::getUrl() }}">&larr; Assigned document test reviews</a>
        <header class="intake-page-heading"><div><p class="intake-eyebrow">{{ $selected->module->name }} · {{ $selected->region->name }}</p><h1>{{ $selected->institution->display_name ?: $selected->institution->name }}</h1><p>Test copy sent {{ $selected->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}. Later applicant changes do not alter these documents.</p></div><span class="intake-status">{{ $selected->newerCopies->isNotEmpty() ? 'Earlier test copy' : 'Current test copy' }}</span></header>
        <div class="intake-info"><strong>Next: check each applicable document</strong><span>Accessibility means you can open it; completeness means the required material is present; document match means it matches the named requirement. URL format alone proves none of these.</span></div>
        @foreach($selected->requirements as $requirement)
            <section class="intake-panel" wire:key="review-requirement-{{ $requirement->id }}">
                <div class="intake-panel-heading"><div><h2>{{ $requirement->requirement->label }}</h2><p>{{ $requirement->is_applicable ? ($requirement->is_required ? 'Required' : 'Optional') : 'Not applicable in this copy' }} · {{ $requirement->active_count }} documents in this test copy</p></div><span class="intake-status">{{ auth()->user()->can('evidence.review.history.view') ? ($requirement->findings->isEmpty() ? 'Needs findings' : 'Findings recorded') : 'Review documents' }}</span></div>
                @if(auth()->user()->can('evidence.reviews.open'))
                @foreach($requirement->items as $index => $item)
                    <div class="intake-document-card"><span>Document {{ $index + 1 }} · {{ $item->revision->provider->name }}</span><div class="intake-document-actions"><a target="_blank" rel="noopener noreferrer" href="{{ route('evidence.review.open', [$selected, $item->revision]) }}">Open document {{ $index + 1 }} <span class="sr-only">for {{ $requirement->requirement->label }} (new tab)</span></a></div></div>
                @endforeach
                @endif
                @if($requirement->is_applicable && auth()->user()->can('evidence.review.findings.create'))
                <form class="intake-form" wire:submit="saveFinding({{ $requirement->id }})">
                    <label class="intake-field"><span>Check to record</span><select wire:loading.attr="disabled" wire:target="saveFinding" aria-label="Check to record" wire:model="findingType.{{ $requirement->id }}"><option value="">Select a check</option><option value="accessibility">Accessibility — can you open it?</option><option value="completeness">Completeness — is the material present?</option><option value="document_match">Document match — is this the right document?</option></select>@error('finding.'.$requirement->id.'.type')<em role="alert">{{ $message }}</em>@enderror</label>
                    <label class="intake-field"><span>Applies to</span><select wire:loading.attr="disabled" wire:target="saveFinding" aria-label="Applies to" wire:model="document.{{ $requirement->id }}"><option value="">This requirement overall</option>@if(auth()->user()->can('evidence.reviews.open'))@foreach($requirement->items as $index => $item)<option value="{{ $item->revision->public_id }}">Document {{ $index + 1 }}</option>@endforeach@endif</select></label>
                    <label class="intake-field"><span>Result</span><select wire:loading.attr="disabled" wire:target="saveFinding" aria-label="Result" wire:model="outcome.{{ $requirement->id }}"><option value="">Select a result</option><option value="meets_test_check">Meets this test check</option><option value="needs_attention">Needs attention</option><option value="unable_to_access">Could not open</option><option value="not_reviewed">Not checked yet</option></select>@error('finding.'.$requirement->id.'.outcome')<em role="alert">{{ $message }}</em>@enderror</label>
                    <label class="intake-field intake-field-wide"><span>Review remarks (optional)</span><small>Describe the check. Do not paste document links, file identifiers, or sensitive document contents.</small><textarea wire:loading.attr="disabled" wire:target="saveFinding" rows="3" wire:model="comment.{{ $requirement->id }}" maxlength="2000"></textarea>@error('finding.'.$requirement->id.'.comment')<em role="alert">{{ $message }}</em>@enderror</label>
                    <div class="intake-form-actions"><button type="submit" class="intake-primary-action" wire:loading.attr="disabled">Save finding<span class="sr-only"> for {{ $requirement->requirement->label }}</span></button></div>
                </form>
                @endif
                @if(auth()->user()->can('evidence.review.history.view'))
                <details class="intake-history"><summary>Finding history ({{ $requirement->findings->count() }})</summary><ol>@forelse($requirement->findings->sortByDesc('reviewed_at') as $finding)<li><div><strong>{{ str($finding->finding_type)->replace('_',' ')->title() }}: {{ str($finding->outcome)->replace('_',' ')->title() }}</strong><p>{{ $finding->reviewer->name }} · {{ $finding->evidence_reference_revision_id ? 'Document check' : 'Requirement check' }}</p>@if($finding->comment)<p>{{ $finding->comment }}</p>@endif</div><time>{{ $finding->reviewed_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time></li>@empty<li>No findings saved yet.</li>@endforelse</ol></details>
                @endif
            </section>
        @endforeach
    @endif
</div>
</x-filament-panels::page>
