<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-theme-init />
    <title>Synthetic review — {{ $manifest->module->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-page">
    <header class="preview-theme-header"><strong>Synthetic staff review only — no responses are saved</strong><x-theme-switcher /></header>
    <main class="mx-auto max-w-4xl space-y-6 p-6">
        <section class="preview-surface">
            <p class="eyebrow">{{ $manifest->module->name }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $manifest->formVersion->definition->name }}</h1>
            <details class="mt-2 osds-muted">
                <summary>Configuration details</summary>
                <p>Publication reference: {{ $manifest->public_id }}</p>
                <p>Configuration reference: {{ substr($manifest->combined_hash, 0, 12) }}</p>
            </details>
        </section>
        @foreach ($manifest->formVersion->sections as $section)
            <section class="preview-surface">
                <h2 class="text-xl font-semibold">{{ $section->title }}</h2>
                @if ($section->description)<p class="mt-1 osds-muted">{{ $section->description }}</p>@endif
                <div class="mt-5 space-y-5">
                    @foreach ($section->questions as $question)
                        <x-questionnaires.field :question="$question" :disabled="true" />
                    @endforeach
                </div>
            </section>
        @endforeach
        <section class="preview-surface">
            <h2 class="text-xl font-semibold">Documentary requirements</h2>
            <div class="mt-4 space-y-4">
                @foreach ($manifest->requirementSetVersion->requirements as $requirement)
                    <div class="questionnaire-requirement"><div class="font-medium">{{ $requirement->label }} @if($requirement->is_required)<span class="questionnaire-required">*</span>@endif</div>
                        <p class="questionnaire-help">{{ $requirement->instructions }}</p>
                        <p class="mt-1 questionnaire-help">Evidence count: {{ $requirement->minimum_evidence }}–{{ $requirement->maximum_evidence }} · {{ $requirement->providers->pluck('name')->join(', ') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
