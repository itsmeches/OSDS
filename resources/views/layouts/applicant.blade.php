<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-theme-init />
    <title>{{ $title ?? 'OSDS Applicant Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php($applicationSandbox = app(\App\Services\Applicants\ApplicantSandboxGuard::class)->allowed())
<a class="skip-link" href="#applicant-content">Skip to main content</a>
<div class="applicant-shell">
    <aside class="applicant-sidebar" aria-label="Applicant portal navigation">
        <a class="applicant-brand" href="{{ route($applicationSandbox ? 'applicant.home' : 'applicant.issuance-demo') }}">
            <span class="applicant-brand-mark" aria-hidden="true">OS</span>
            <span>CHED OSDS<small>Applicant portal</small></span>
        </a>
        <button class="applicant-menu-toggle secondary" type="button" aria-expanded="false" aria-controls="applicant-navigation" data-applicant-menu>Menu</button>
        <div class="applicant-navigation" id="applicant-navigation">
        <nav aria-label="Applicant pages">
            <a href="{{ route('applicant.issuance-demo') }}" @if(request()->routeIs('applicant.issuance-demo')) aria-current="page" @endif>Unlinked layout preview</a>
            <a href="{{ route('applicant.forms') }}" @if(request()->routeIs('applicant.forms')) aria-current="page" @endif>Downloadable forms</a>
            @if($applicationSandbox)
            <a href="{{ route('applicant.issuance-requests') }}" @if(request()->routeIs('applicant.issuance-requests')) aria-current="page" @endif>Issuance requests</a>
            <a @class(['active' => request()->routeIs('applicant.start')]) href="{{ route('applicant.start') }}"><span aria-hidden="true">＋</span> Start application</a>
            <a @class(['active' => request()->routeIs('applicant.home', 'applicant.drafts.*', 'applicant.applications.*')]) @if(request()->routeIs('applicant.home')) aria-current="page" @endif href="{{ route('applicant.home') }}"><span aria-hidden="true">▤</span> My applications</a>
            <a @class(['active' => request()->routeIs('applicant.account')]) href="{{ route('applicant.account') }}"><span aria-hidden="true">◎</span> Account and organization</a>
            @endif
        </nav>
        <div class="applicant-sidebar-footer">
            <div class="theme-switcher" aria-label="Color theme">
                <button type="button" data-theme-option="light">Light</button>
                <button type="button" data-theme-option="dark">Dark</button>
                <button type="button" data-theme-option="system">System</button>
            </div>
            <form method="post" action="{{ route('applicant.logout') }}">@csrf<button class="sign-out" type="submit">Sign out</button></form>
        </div>
        </div>
    </aside>
    <main class="applicant-main">
        <div class="sandbox-banner" role="status"><span aria-hidden="true">ⓘ</span><span><strong>Synthetic applicant sandbox</strong><small>No official application is submitted.</small></span></div>
        <div class="applicant-content" id="applicant-content" tabindex="-1">{{ $slot ?? '' }}@yield('content')</div>
    </main>
</div>
</body>
</html>
