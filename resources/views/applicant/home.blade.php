@extends('layouts.applicant')
@section('content')
<header class="page-heading"><div><p class="eyebrow">Applicant workspace</p><h1>My applications</h1><p>Resume a saved draft, make requested corrections, or check what happens next.</p></div><a class="primary button" href="{{ route('applicant.start') }}">Start application</a></header>
<div class="applicant-guidance">
<p class="applicant-workspace-intro">An application and a demonstration-document request are separate. Start with your application; an approved case may have a request available.</p>
<div class="card-list">
@forelse($applications as $application)
@php($draft = $application->currentWorkingRevision?->draft)
@php($ended = $application->manifest->retirement || $application->manifest->effective_until?->isPast())
<article class="portal-card application-list-card">
    <div><h2>{{ $application->manifest->module->name }}</h2><p>{{ $application->institution->display_name ?: $application->institution->name }}</p><x-applicant-status :state="$application->state" />
    <p class="field-help">{{ \App\Support\ApplicantApplicationGuidance::description($application->state) }}</p>
    @if($ended)<p class="applicant-availability-note">{{ \App\Support\ApplicantApplicationGuidance::publicationNotice(true) }}</p>@endif</div>
    <div class="application-card-action">@if($draft && in_array($application->state, ['draft', 'correction_requested'], true) && !$ended)<a class="secondary button" href="{{ route('applicant.drafts.edit', $draft) }}">{{ $application->state === 'correction_requested' ? 'Make corrections' : 'Resume draft' }}</a>@elseif($draft && $ended)<a class="secondary button" href="{{ route('applicant.drafts.edit', $draft) }}">View saved draft</a>@else<a class="secondary button" href="{{ route('applicant.applications.show', $application) }}">View status and history</a>@endif
    @if($application->state === 'approved')<a class="application-back-link applicant-guidance-link" href="{{ route('applicant.issuance-requests', ['application' => $application->public_id]) }}">Check issuance-request availability</a>@endif
    <small>{{ $draft?->last_saved_at ? 'Answers saved '.$draft->last_saved_at->timezone('Asia/Manila')->format('M j, Y g:i A') : 'Updated '.$application->updated_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</small></div>
</article>
@empty<div class="portal-card"><h2>No applications available</h2><p>No applications are available in this workspace. Start an application to view the organizations and questionnaires currently available to your account.</p></div>@endforelse
</div>
<nav class="applicant-resource-links" aria-label="Application resources"><a href="{{ route('applicant.issuance-requests') }}">Issuance requests</a><a href="{{ route('applicant.forms') }}">Download blank forms</a></nav>
</div>
@endsection
