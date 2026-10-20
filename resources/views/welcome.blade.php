<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CHED OSDS Management Platform project foundation">
    <title>OSDS Management Platform</title>
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-page">
    <header class="public-header">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-5">
            <div>
                <p class="eyebrow">CHED &middot; OSDS</p>
                <p class="text-lg font-bold">Management Platform</p>
            </div>
            <div class="public-header-actions"><x-theme-switcher /><a href="{{ route('applicant.login') }}" class="secondary">Applicant sign in</a><a href="{{ url('/admin') }}" class="primary">Staff sign in</a></div>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-20">
        <span class="status">Project foundation</span>
        <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">A shared foundation for OSDS services</h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 osds-muted">This initial site establishes the platform infrastructure. Applicant services, regional workflows, and program features will be added after their requirements are approved.</p>
        <p class="mt-8 osds-muted">No public application submission is available yet.</p>
    </main>
    <footer class="public-footer">Commission on Higher Education &middot; Office of Student Development and Services</footer>
</body>
</html>
