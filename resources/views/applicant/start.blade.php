@extends('layouts.applicant')

@section('content')
<header class="page-heading">
    <div>
        <p class="eyebrow">New draft</p>
        <h1>Start application</h1>
        <p>Choose your organization, then choose a questionnaire. Creating a draft does not submit it.</p>
    </div>
</header>

@if ($institutions->isEmpty())
    <section class="portal-card empty-state" role="status">
        <span class="empty-state-icon" aria-hidden="true">◎</span>
        <div>
            <h2>No verified organization available</h2>
            <p>Your synthetic applicant account needs an active, verified organization representation before you can start a draft.</p>
            <a class="secondary button" href="{{ route('applicant.account') }}">View account and organization</a>
        </div>
    </section>
@elseif ($manifests->isEmpty())
    <section class="portal-card empty-state" role="status">
        <span class="empty-state-icon" aria-hidden="true">▤</span>
        <div>
            <h2>No questionnaires are available yet</h2>
            <p>An administrator must publish an approved questionnaire specifically to the applicant sandbox. Staff review publications are kept separate and do not appear here.</p>
            <p class="empty-state-help">Ask the sandbox administrator to publish the questionnaire, then refresh this page.</p>
            <a class="secondary button" href="{{ route('applicant.home') }}">Return to my applications</a>
        </div>
    </section>
@else
    <form class="portal-card form-stack start-application-form" method="post" action="{{ route('applicant.drafts.create') }}">
        @csrf
        <div class="form-section-heading">
            <span>1</span>
            <div><h2>Choose an organization</h2><p>The application will be associated with this represented organization.</p></div>
        </div>
        <label>Organization
            <select name="institution_id" required data-organization-select>
                <option value="">Select organization</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->id }}" data-modules="{{ ($modulesByInstitution[$institution->id] ?? collect())->implode(',') }}" @selected(old('institution_id') == $institution->id)>{{ $institution->display_name ?: $institution->name }}</option>
                @endforeach
            </select>
        </label>

        <div class="form-section-heading">
            <span>2</span>
            <div><h2>Choose a questionnaire</h2><p>Only questionnaires available to the organization you select will appear.</p></div>
        </div>
        <label>Questionnaire
            <select name="manifest_id" required data-questionnaire-select>
                <option value="">Select questionnaire</option>
                @foreach ($manifests as $manifest)
                    <option value="{{ $manifest->id }}" data-module="{{ $manifest->module_id }}" @selected(old('manifest_id') == $manifest->id)>{{ $manifest->module->name }}</option>
                @endforeach
            </select>
        </label>

        @if ($errors->any())
            <div class="auth-error" role="alert"><strong>Unable to start the draft.</strong><p>{{ $errors->first() }}</p></div>
        @endif

        <p data-questionnaire-availability class="field-help" role="status">Choose an organization to see its available questionnaires.</p>
        <div class="form-actions"><a class="secondary button" href="{{ route('applicant.home') }}">Cancel</a><button class="primary" type="submit">Create draft</button></div>
    </form>
@endif
@endsection
