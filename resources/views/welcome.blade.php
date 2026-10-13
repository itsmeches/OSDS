<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CHED OSDS Management Platform project foundation">
    <title>OSDS Management Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-5">
            <div>
                <p class="text-sm font-semibold tracking-wide text-blue-800">CHED · OSDS</p>
                <p class="text-lg font-bold">Management Platform</p>
            </div>
            <a href="{{ url('/admin') }}" class="rounded-md border border-blue-800 px-4 py-2 text-sm font-semibold text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-800">Staff sign in</a>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-20">
        <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-900">Project foundation</span>
        <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">A shared foundation for OSDS services</h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-700">This initial site establishes the platform infrastructure. Applicant services, regional workflows, and program features will be added after their requirements are approved.</p>
        <p class="mt-8 text-sm text-slate-600">No public application submission is available yet.</p>
    </main>
    <footer class="border-t border-slate-200 bg-white px-6 py-6 text-center text-sm text-slate-600">Commission on Higher Education · Office of Student Development and Services</footer>
</body>
</html>
