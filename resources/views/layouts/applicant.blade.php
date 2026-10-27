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
            @if($applicationSandbox)
            <a @class(['active' => request()->routeIs('applicant.home', 'applicant.drafts.*', 'applicant.applications.*')]) @if(request()->routeIs('applicant.home')) aria-current="page" @endif href="{{ route('applicant.home') }}"><x-filament::icon icon="heroicon-o-inbox" aria-hidden="true" /> <span>My applications</span></a>
            <a @class(['active' => request()->routeIs('applicant.start')]) @if(request()->routeIs('applicant.start')) aria-current="page" @endif href="{{ route('applicant.start') }}"><x-filament::icon icon="heroicon-o-plus-circle" aria-hidden="true" /> <span>Start application</span></a>
            <a @class(['active' => request()->routeIs('applicant.issuance-requests')]) @if(request()->routeIs('applicant.issuance-requests')) aria-current="page" @endif href="{{ route('applicant.issuance-requests') }}"><x-filament::icon icon="heroicon-o-clipboard-document-list" aria-hidden="true" /> <span>Issuance requests</span></a>
            @endif
            <a @class(['active' => request()->routeIs('applicant.forms')]) @if(request()->routeIs('applicant.forms')) aria-current="page" @endif href="{{ route('applicant.forms') }}"><x-filament::icon icon="heroicon-o-arrow-down-tray" aria-hidden="true" /> <span>Download blank forms</span></a>
            @if($applicationSandbox)
            <a @class(['active' => request()->routeIs('applicant.account')]) @if(request()->routeIs('applicant.account')) aria-current="page" @endif href="{{ route('applicant.account') }}"><x-filament::icon icon="heroicon-o-building-library" aria-hidden="true" /> <span>Account and organization</span></a>
            @endif
        </nav>
        <section class="applicant-secondary-navigation" aria-labelledby="demonstration-tools-heading">
            <h2 id="demonstration-tools-heading">Secondary demonstration tools</h2>
            <p>Try a sample layout. This does not submit an issuance request or create a case-linked document.</p>
            <nav aria-label="Secondary demonstration tools">
                <a @class(['active' => request()->routeIs('applicant.issuance-demo')]) @if(request()->routeIs('applicant.issuance-demo')) aria-current="page" @endif href="{{ route('applicant.issuance-demo') }}"><x-filament::icon icon="heroicon-o-eye" aria-hidden="true" /> <span>Layout preview</span></a>
            </nav>
        </section>
        <div class="applicant-sidebar-footer">
            <x-theme-switcher />
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
