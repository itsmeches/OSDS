@extends('layouts.applicant')
@section('content')
<header class="page-heading"><div><p class="eyebrow">Applicant workspace</p><h1>My applications</h1><p>Resume a saved draft, make requested corrections, or check what happens next.</p></div><a class="primary button" href="{{ route('applicant.start') }}">Start application</a></header>
<div class="card-list">
@forelse($applications as $application)
@php($draft = $application->currentWorkingRevision?->draft)
@php($ended = $application->manifest->retirement || $application->manifest->effective_until?->isPast())
<article class="portal-card application-list-card">
    <div><h2>{{ $application->manifest->module->name }}</h2><p>{{ $application->institution->display_name ?: $application->institution->name }}</p><x-applicant-status :state="$application->state" />
    <p class="field-help">{{ $ended ? 'This questionnaire has closed. Saved information remains available to view.' : match($application->state) { 'draft' => 'Continue your questionnaire and document links. Nothing has been sent yet.', 'correction_requested' => 'Read the requested changes, update your saved copy, then send it again.', default => 'CHEDRO has your saved test copy. Check status for updates; no further action is needed unless corrections are requested.' } }}</p></div>
    <div class="application-card-action">@if($draft && in_array($application->state, ['draft', 'correction_requested'], true) && !$ended)<a class="secondary button" href="{{ route('applicant.drafts.edit', $draft) }}">{{ $application->state === 'correction_requested' ? 'Make corrections' : 'Resume draft' }}</a>@elseif($draft && $ended)<a class="secondary button" href="{{ route('applicant.drafts.edit', $draft) }}">View saved draft</a>@else<a class="secondary button" href="{{ route('applicant.applications.show', $application) }}">View status and history</a>@endif
    <small>{{ $draft?->last_saved_at ? 'Answers saved '.$draft->last_saved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : 'Updated '.$application->updated_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</small></div>
</article>
@empty<div class="portal-card"><h2>No applications yet</h2><p>Start a synthetic questionnaire when you are ready.</p></div>@endforelse
</div>
@endsection
