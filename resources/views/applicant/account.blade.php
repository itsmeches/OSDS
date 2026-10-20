@extends('layouts.applicant')
@section('content')
<header class="page-heading"><div><p class="eyebrow">Account</p><h1>Account and organization</h1><p>You can use an organization only while your representation is verified and within its effective dates.</p></div></header>
<div class="card-list">
@forelse($institutions as $institution)
    @php($grant = $institution->pivot)
    @php($state = $grant->revoked_at ? 'Revoked' : (!$grant->verified_at ? 'Awaiting verification' : ($grant->starts_at && \Illuminate\Support\Carbon::parse($grant->starts_at)->isFuture() ? 'Not started' : ($grant->ends_at && \Illuminate\Support\Carbon::parse($grant->ends_at)->isPast() ? 'Expired' : 'Verified and active'))))
    <article class="portal-card"><h2>{{ $institution->display_name ?: $institution->name }}</h2><p>{{ str($institution->organization_type)->replace('_', ' ')->title() }}</p><span class="status">{{ $state }}</span><p class="field-help">Organization type does not establish accreditation or eligibility for a questionnaire.</p></article>
@empty
    <section class="portal-card"><h2>No organizations linked</h2><p>Contact the CHEDRO staff member who created your test account to check your representation.</p></section>
@endforelse
</div>
@endsection
