<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-theme-init />
    <title>Applicant sign in · CHED OSDS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <header class="auth-topbar">
        <a class="auth-brand" href="{{ url('/') }}"><span class="applicant-brand-mark" aria-hidden="true">OS</span><span>CHED OSDS<small>Management Platform</small></span></a>
        <div class="theme-switcher auth-theme" aria-label="Color theme">
            <button type="button" data-theme-option="light">Light</button><button type="button" data-theme-option="dark">Dark</button><button type="button" data-theme-option="system">System</button>
        </div>
    </header>
    <main class="auth-layout">
        <section class="auth-intro" aria-labelledby="applicant-login-title">
            <p class="eyebrow">Applicant portal</p>
            <h1 id="applicant-login-title">{{ $applicationSandbox ? 'Continue your test application' : 'Sign in to applicant resources' }}</h1>
            <p>{{ $applicationSandbox ? 'Sign in with an active synthetic SCHOOL account to create, save, and review a questionnaire draft.' : 'Use an active authorized applicant account to download blank forms and answer the demonstration issuance form. Application submission is unavailable in this local setup.' }}</p>
            <div class="auth-sandbox-note"><span aria-hidden="true">ⓘ</span><span><strong>Sandbox environment</strong>This portal uses synthetic records for testing. Nothing here is an official application or submission.</span></div>
        </section>
        <section class="auth-card" aria-labelledby="sign-in-heading">
            <div class="auth-card-heading"><p class="eyebrow">Welcome back</p><h2 id="sign-in-heading">Applicant sign in</h2><p>{{ $applicationSandbox ? 'Enter the credentials provided for your synthetic applicant account.' : 'Enter the credentials provided for your applicant account.' }}</p></div>
            <form class="form-stack auth-form" method="post" action="{{ route('applicant.login.store') }}">
                @csrf
                <label for="email">Email address<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
                <label for="password">Password<input id="password" name="password" type="password" autocomplete="current-password" required></label>
                @error('email')<p class="error auth-error" role="alert">{{ $message }}</p>@enderror
                <button class="primary auth-submit" type="submit">Sign in</button>
            </form>
            <p class="auth-help">Need account assistance? Contact the CHEDRO staff member who created your test account.</p>
        </section>
    </main>
</body>
</html>
