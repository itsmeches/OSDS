@extends('layouts.applicant')
@section('content')
@php($ended = $application->manifest->retirement || $application->manifest->effective_until?->isPast())
<header class="page-heading"><div><a class="application-back-link" href="{{ route('applicant.home') }}">&larr; My applications</a><p class="eyebrow">Test application status</p><h1>{{ $application->institution->display_name ?: $application->institution->name }}</h1><x-applicant-status :state="$application->state" /></div></header>
@if($ended)<div class="notice" role="status">This questionnaire version has ended. Your application and history are preserved, but editing and resubmission are unavailable.</div>@endif
@if($application->state === 'correction_requested' && $correction)
    <section class="portal-card correction-instructions"><h2>What you need to change</h2><p>{{ $correction->applicant_summary }}</p><ol>@foreach($correction->items as $item)<li>{{ $item->instruction }}</li>@endforeach</ol><p>Open your correction copy, make the changes, save, then review and send it again. Your previous sent copy remains unchanged.</p>
    @if($application->currentWorkingRevision && !$ended)<a class="primary button" href="{{ route('applicant.drafts.edit', $application->currentWorkingRevision->draft) }}">Make corrections</a>@endif</section>
@elseif($application->state === 'draft')
    <section class="portal-card tracking-summary"><h2>Your draft has not been sent</h2><p>Complete the questionnaire and required document links, then review your test application.</p>@if($application->currentWorkingRevision && !$ended)<a class="primary button" href="{{ route('applicant.drafts.edit', $application->currentWorkingRevision->draft) }}">Resume draft</a>@endif</section>
@else
    <section class="portal-card tracking-summary"><h2>{{ $application->state === 'resubmitted' ? 'Your corrections have been sent' : 'CHEDRO has your test copy' }}</h2><p>{{ $application->state === 'under_screening' ? 'A reviewer is screening your saved copy. If changes are needed, they will appear here.' : 'Your saved copy is waiting for CHEDRO screening. You do not need to send it again.' }}</p><p class="field-help">This is a synthetic test workflow, not an official decision or issuance.</p></section>
@endif
<section class="portal-card tracking-history"><h2>Status history</h2><ol>@forelse($application->history as $event)<li><x-applicant-status :state="$event->to_state" /><p>{{ $event->applicant_summary }}</p><small>{{ $event->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</small></li>@empty<li>No sent copy yet. Continue your draft when you are ready.</li>@endforelse</ol></section>
<details class="portal-card"><summary>Test reference for support</summary><p>{{ $application->public_id }}</p><p class="field-help">This identifies a synthetic test application. It is not an official tracking number.</p></details>
@endsection
