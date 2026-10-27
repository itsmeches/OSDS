@extends('layouts.applicant')
@section('content')
<header class="page-heading"><div><p class="eyebrow">Applicant resources</p><h1>Download blank forms</h1><p>Get the current blank forms you need for your application.</p></div></header>
<div class="downloads-applicant-note"><p>Download and complete the relevant form, then follow the document instructions in your application. Downloading a form does not submit an application.</p></div>
<div class="downloads-list-heading"><h2>Available forms</h2><span class="downloads-count">{{ $forms->total() }} {{ $forms->total() === 1 ? 'form' : 'forms' }}</span></div>
<div class="card-list downloads-catalogue">
@forelse($forms as $form)
    <article class="portal-card questionnaire-card downloads-card" aria-labelledby="applicant-form-{{ $form->id }}">
        <div class="downloads-card-title"><span class="downloads-file-type" aria-label="File format">{{ strtoupper($form->currentVersion->extension) }}</span><div><h3 id="applicant-form-{{ $form->id }}">{{ $form->title }}</h3><p class="downloads-help">{{ $form->module?->name ?? 'General / both modules' }}</p></div></div>
        @if($form->description)<p class="downloads-description">{{ $form->description }}</p>@endif
        <div class="downloads-applicant-footer"><dl class="downloads-metadata"><div><dt>Current version</dt><dd>{{ $form->currentVersion->version_label }}</dd></div><div><dt>File size</dt><dd>{{ number_format($form->currentVersion->size / 1024, 1) }} KB</dd></div></dl><a class="button primary" aria-label="Download {{ strtoupper($form->currentVersion->extension) }}: {{ $form->title }}" href="{{ route('forms.download', [$form->public_id, $form->current_version_id]) }}">Download {{ strtoupper($form->currentVersion->extension) }}</a></div>
    </article>
@empty
    <section class="portal-card questionnaire-card downloads-empty"><h3>No forms available yet</h3><p>OSDS has not published any forms for download. Please check again later.</p></section>
@endforelse
</div>
{{ $forms->links() }}
@endsection
