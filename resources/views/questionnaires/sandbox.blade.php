<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Synthetic review — {{ $manifest->module->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="bg-amber-300 px-6 py-3 text-center font-bold">Synthetic staff review only — no responses are saved</header>
    <main class="mx-auto max-w-4xl space-y-6 p-6">
        <section class="rounded-xl bg-white p-6 shadow">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">{{ $manifest->module->name }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $manifest->formVersion->definition->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">Manifest {{ $manifest->public_id }} · Configuration {{ substr($manifest->combined_hash, 0, 12) }}</p>
        </section>
        @foreach ($manifest->formVersion->sections as $section)
            <section class="rounded-xl bg-white p-6 shadow">
                <h2 class="text-xl font-semibold">{{ $section->title }}</h2>
                @if ($section->description)<p class="mt-1 text-slate-600">{{ $section->description }}</p>@endif
                <div class="mt-5 space-y-5">
                    @foreach ($section->questions as $question)
                        <div><div class="font-medium">{{ $question->label }} @if($question->is_required)<span class="text-red-600">*</span>@endif</div>
                            @if($question->help_text)<div class="text-sm text-slate-500">{{ $question->help_text }}</div>@endif
                            <div class="mt-2 rounded border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-500">{{ str($question->type)->replace('_', ' ')->title() }} preview</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="text-xl font-semibold">Documentary requirements</h2>
            <div class="mt-4 space-y-4">
                @foreach ($manifest->requirementSetVersion->requirements as $requirement)
                    <div class="rounded border p-4"><div class="font-medium">{{ $requirement->label }} @if($requirement->is_required)<span class="text-red-600">*</span>@endif</div>
                        <p class="text-sm text-slate-600">{{ $requirement->instructions }}</p>
                        <p class="mt-1 text-xs text-slate-500">Evidence count: {{ $requirement->minimum_evidence }}–{{ $requirement->maximum_evidence }} · {{ $requirement->providers->pluck('name')->join(', ') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
