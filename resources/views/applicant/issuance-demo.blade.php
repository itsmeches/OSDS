@extends('layouts.applicant')
@section('content')
<header class="page-heading"><div><p class="eyebrow">Demonstration only</p><h1>{{ $form?->definition['title'] ?? 'Demonstration issuance form' }}</h1><p>No eligibility, approval or official certificate is granted.</p></div></header>
<div class="downloads-actions"><a class="button secondary" href="{{ route('applicant.forms') }}">Download blank forms</a></div>
<div class="downloads-applicant-note"><strong>DRAFT / DEMONSTRATION ONLY</strong><p>Use fictional details. This separate preview does not submit an application or alter a case. Answers are not saved as a submission; keep this tab open while reviewing or printing the result.</p></div>
@if(!$form || !$form->definition['enabled'] || !$directory)
    <section class="portal-card"><h2>Demonstration form unavailable</h2><p>OSDS must publish the form and configure a demonstration office before it can be answered.</p></section>
@else
    @if($errors->any())<div class="portal-card" role="alert"><h2>Preview not created</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Keep the original form tab open. If OSDS changed the form, reload it before another explicit preview.</p></div>@endif
    <section class="portal-card questionnaire-card">
        <p class="downloads-description">{{ $form->definition['instructions'] }}</p>
        <form method="POST" action="{{ route('applicant.issuance-demo.preview') }}" target="_blank" class="downloads-form issuance-applicant-form">
            @csrf
            <input type="hidden" name="form_revision" value="{{ $form->id }}">
            <input type="hidden" name="directory_revision" value="{{ $directory->id }}">
            <div class="downloads-field"><label for="app-issuance-kind">{{ $form->definition['labels']['kind'] }} (required)</label><select id="app-issuance-kind" name="kind" required><option value="cor">Certificate of Registration — demonstration</option><option value="acsa">Authority to Conduct Sports Activity — demonstration</option></select></div>
            <div class="downloads-field"><label for="app-issuance-organization">{{ $form->definition['labels']['organization'] }} (required)</label><input id="app-issuance-organization" name="organization" value="{{ old('organization') }}" required maxlength="200" autocomplete="off"></div>
            <div class="downloads-field downloads-field-wide"><label for="app-issuance-address">{{ $form->definition['labels']['address'] }} (required)</label><textarea id="app-issuance-address" name="address" required maxlength="400" rows="2">{{ old('address') }}</textarea></div>
            <div class="downloads-field"><label for="app-issuance-date">{{ $form->definition['labels']['date'] }} (required)</label><input id="app-issuance-date" name="date" type="date" value="{{ old('date') }}" required></div>
            <div class="downloads-field"><label for="app-issuance-office">{{ $form->definition['labels']['office_key'] }} (required)</label><select id="app-issuance-office" name="office_key" required><option value="">Select a demonstration office</option>@foreach($directory->offices as $key => $office)<option value="{{ $key }}" @selected(old('office_key') === $key)>{{ $office['label'] }}</option>@endforeach</select><p class="downloads-help">The selected office supplies the saved director and designation. It does not determine application jurisdiction or grant authority.</p></div>
            @foreach($form->definition['extras'] as $field)
                <div class="downloads-field downloads-field-wide"><label for="app-extra-{{ $field['key'] }}">{{ $field['label'] }} ({{ $field['required'] ? 'required' : 'optional' }})</label>
                    @if($field['type'] === 'textarea')<textarea id="app-extra-{{ $field['key'] }}" name="extras[{{ $field['key'] }}]" rows="3" maxlength="1000" @required($field['required'])>{{ old('extras.'.$field['key']) }}</textarea>
                    @else<input id="app-extra-{{ $field['key'] }}" name="extras[{{ $field['key'] }}]" type="{{ $field['type'] }}" value="{{ old('extras.'.$field['key']) }}" maxlength="1000" @required($field['required'])>@endif
                </div>
            @endforeach
            <div class="downloads-field downloads-field-wide"><label><input type="checkbox" name="fictional_confirmation" value="1" required> I am using fictional details and understand this is not an issued certificate.</label></div>
            <div class="downloads-field-wide"><button type="submit" class="button primary">Preview printable draft (new tab)</button><p class="downloads-help">Form revision {{ $form->id }} · Directory revision {{ $directory->id }} · Watermarked template demo-v3. Print / Save as PDF in the preview tab. No email is sent.</p></div>
        </form>
    </section>
@endif
@endsection
