<div class="issuance-requests-workspace">
@php
    $displayIssuanceAnswer = static function (array $question, mixed $value): string {
        if (is_bool($value) || $question['type'] === 'declaration') {
            return in_array($value, [true, 1, '1', 'yes'], true) ? 'Confirmed' : 'Not confirmed';
        }
        if ($value === null || $value === '') { return 'Not provided'; }
        $options = collect($question['options'] ?? [])->pluck('label', 'key');
        if (is_array($value)) { return $value === [] ? 'None selected' : implode(', ', array_map(fn ($key) => $options[$key] ?? $key, $value)); }
        if ($question['type'] === 'yes_no') { return $value === 'yes' ? 'Yes' : 'No'; }
        return $question['type'] === 'single_choice' ? (string) ($options[$value] ?? $value) : (string) $value;
    };
@endphp
<header class="page-heading"><h1>Issuance requests</h1><p>Case-linked synthetic demonstration only. No official certificate, release or PDF is produced.</p></header>
@if($feedback)<p class="notice" role="status">{{ $feedback }}</p>@endif
@if($errors->any())<div class="field-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(!$case)
    @foreach(['registration'=>'COR','acsa'=>'ACSA'] as $module=>$label)<section class="portal-card"><h2>{{ $label }} cases</h2>@forelse($cases->filter(fn($c)=>$c->module->code===$module) as $entry)<article><h3>{{ $entry->institution->name }}</h3><p>Application status: {{ $entry->state }}</p><a href="{{ route('applicant.issuance-requests',['application'=>$entry->public_id]) }}">Inspect eligibility and requests</a></article>@empty<p>No accessible {{ $label }} application. Submit and obtain a qualifying Director decision first.</p>@endforelse</section>@endforeach
@else
    <a href="{{ route('applicant.issuance-requests') }}">All issuance cases</a>
    <section class="portal-card"><h2>{{ $case->module->name }} request</h2><p>Application status: {{ ucfirst(str_replace('_',' ',$case->state)) }}</p>@if($reason)<p class="notice" role="status">{{ $reason }}</p>@endif
    @if($identity)<h3>Approved details — read only</h3><dl><dt>Certificate type</dt><dd>{{ strtoupper($identity['certificate_type']) }} demonstration request</dd><dt>Approved institution</dt><dd>{{ $identity['approved_institution_name'] }}</dd><dt>Organization name for the document</dt><dd>{{ $identity['organization'] }}</dd><dt>Address</dt><dd>{{ $identity['address'] }}</dd><dt>CHED regional office</dt><dd>{{ $identity['region'] }}</dd><dt>Director-approved application revision</dt><dd>Revision {{ $case->currentSubmission->submission_sequence }}</dd></dl>@endif
    <details><summary>Case reference for support</summary><p>{{ $case->public_id }}</p></details></section>
    @if($definition)<section class="portal-card"><h2>{{ $definition->title }} — version {{ $definition->version }}</h2><p>{{ $definition->instructions }}</p>
    @if($saved?->state==='submitted')<p class="notice" role="status">Submitted demonstration request {{ $saved->public_id }}. No certificate has been generated.</p><dl>@foreach($saved->definition_snapshot['questions'] as $q)<dt>{{ $q['label'] }}</dt><dd>{{ $displayIssuanceAnswer($q,$saved->answers[$q['key']]??null) }}</dd>@endforeach</dl>
    @elseif(!$reason)
    <form wire:submit="save(true)">@foreach($definition->definition['questions'] as $q)@if(app(\App\Services\Questionnaires\ConditionalLogic::class)->evaluate($q['condition'],$answers))<x-questionnaires.field :question="$q + ['is_required'=>$q['required'],'help_text'=>$q['help']]" :model="'answers.'.$q['key']" />@endif@endforeach
    <h3>Evidence already in the approved submission</h3>@forelse($definition->definition['requirements'] as $r)<p>{{ $r['label'] }}: {{ $r['instructions'] }}</p>@empty<p>No additional evidence requirements configured.</p>@endforelse
    <p>Review the approved read-only details before submitting. After uncertainty, inspect this saved case before repeating an action.</p><div class="inline-actions"><button class="secondary" type="button" wire:click="save(false)" wire:loading.attr="disabled">Save issuance draft</button><button class="primary" type="submit" wire:loading.attr="disabled">Submit demonstration request</button></div><span role="status" wire:loading>Saving request…</span></form>
    @endif</section>@endif
    @if($saved)<section class="portal-card"><h2>Request history</h2><ol>@foreach(\Illuminate\Support\Facades\DB::table('issuance_history')->where('request_id',$saved->id)->orderBy('id')->get() as $event)<li>{{ $event->event==='draft_saved'?'Draft saved':'Demonstration request submitted' }} · {{ \Illuminate\Support\Carbon::parse($event->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A') }}</li>@endforeach</ol></section>@endif
    <section class="portal-card"><h2>Saved versions for this case</h2>@forelse(\App\Models\IssuanceRequest::where('application_id',$case->id)->where('actor_id',auth()->id())->orderByDesc('id')->get() as $prior)<details><summary>{{ ucfirst($prior->state) }} · Form version {{ \App\Models\IssuanceDefinition::find($prior->definition_id)->version }}</summary>@if($prior->state==='draft')<p>A draft tied to a withdrawn version remains preserved. Start the current version explicitly; earlier entries are not automatically copied.</p>@else<p>This submitted request is read-only. No certificate or PDF has been generated.</p>@endif<dl>@foreach($prior->definition_snapshot['questions'] as $q)<dt>{{ $q['label'] }}</dt><dd>{{ $displayIssuanceAnswer($q,$prior->answers[$q['key']]??null) }}</dd>@endforeach</dl></details>@empty<p>No saved issuance request yet.</p>@endforelse</section>
@endif
</div>
