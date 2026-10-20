<x-filament-panels::page>
    @if (! $selectedModule)
        <section class="questionnaire-index-intro"><div><p class="osds-eyebrow">Questionnaire workspace</p><h2>Choose a questionnaire service</h2><p>Continue an existing questionnaire or create one under an approved OSDS service.</p></div></section>
        <div class="osds-module-list">
            @forelse ($modules as $module)
                @php $status = $moduleStatus($module); @endphp
                <section class="osds-workflow-card osds-module-card" aria-labelledby="module-{{ $module->id }}">
                    <div class="flex items-start justify-between gap-4"><div><p class="osds-eyebrow">Approved service</p><h2 id="module-{{ $module->id }}" class="mt-1 text-lg font-semibold">{{ $module->name }}</h2></div><x-filament::badge :color="\App\Support\QuestionnairePresentation::statusColor($status)" :icon="\App\Support\QuestionnairePresentation::statusIcon($status)">{{ \App\Support\QuestionnairePresentation::statusLabel($status) }}</x-filament::badge></div>
                    <p class="osds-muted mt-3 text-sm">Open its questionnaire overview, current status, and next available action.</p>
                    <div class="mt-5"><x-filament::button :href="\App\Filament\Pages\Questionnaires::getUrl(['module' => $module->code])" tag="a">View questionnaires <span aria-hidden="true">&rarr;</span></x-filament::button></div>
                </section>
            @empty
                <section class="osds-workflow-card"><h2 class="text-lg font-semibold">No approved services are available</h2><p class="osds-muted mt-2 text-sm">An authorized administrator must configure an OSDS service before a questionnaire can be created.</p></section>
            @endforelse
        </div>
    @else
        <nav class="builder-breadcrumbs" aria-label="Breadcrumb"><a href="{{ \App\Filament\Pages\Questionnaires::getUrl() }}">Questionnaires</a><span aria-hidden="true">&rsaquo;</span><span>{{ $selectedModule->name }}</span></nav>
        @php
            $exactPair = $draftFormVersions->count() === 1 && $draftRequirementVersions->count() === 1;
            $hasDraftChoices = $draftFormVersions->isNotEmpty() && $draftRequirementVersions->isNotEmpty();
            $workingForm = $exactPair ? $draftFormVersions->first() : null;
            $workingRequirements = $exactPair ? $draftRequirementVersions->first() : null;
            $latestForm = $formVersions->first();
            $status = $hasDraftChoices ? 'draft' : ($latestForm?->state ?? 'draft');
        @endphp
        <section class="questionnaire-overview-card">
            <div class="questionnaire-overview-heading"><div><p class="osds-eyebrow">{{ $selectedModule->name }}</p><h2>{{ $hasDraftChoices && !$exactPair ? 'Choose a questionnaire draft' : ($workingForm?->definition?->name ?? $latestForm?->definition?->name ?? 'Questionnaire configuration') }}</h2><p>@if ($hasDraftChoices && !$exactPair) Several drafts are available. Choose the exact questionnaire and document pair using Continue editing above. @elseif ($workingForm && $workingRequirements) Continue editing questions and documents in the Form Builder. @elseif ($formVersions->contains('state', 'in_review')) This configuration is awaiting an Admin decision. @elseif ($formVersions->contains('state', 'published')) The published configuration is read-only. Create a new draft to revise it. @else Create a questionnaire to begin. @endif</p></div><x-filament::badge :color="\App\Support\QuestionnairePresentation::statusColor($status)" :icon="\App\Support\QuestionnairePresentation::statusIcon($status)">{{ \App\Support\QuestionnairePresentation::statusLabel($status) }}</x-filament::badge></div>
            @if ($workingForm && $workingRequirements)
                <div class="questionnaire-overview-metrics"><article><strong>{{ $workingForm->sections->count() }}</strong><span>Sections</span></article><article><strong>{{ $workingForm->sections->sum(fn ($section) => $section->questions->count()) }}</strong><span>Questions</span></article><article><strong>{{ $workingRequirements->requirements->count() }}</strong><span>Required documents</span></article><article><strong>{{ $workingForm->updated_at?->timezone('Asia/Manila')->format('d M Y, g:i A') }}</strong><span>Last updated</span></article></div>
                <div class="questionnaire-overview-next">
                    @if ($builderUrl)
                        <x-filament::button :href="$builderUrl" tag="a" icon="heroicon-o-pencil-square">Continue editing</x-filament::button><p>The builder contains editing, preview, configuration checking, and review preparation.</p>
                    @else
                        <div><strong>Choose the exact draft pair</strong><p>More than one questionnaire or document draft is available. Use <strong>Continue editing</strong> above and select both versions explicitly.</p></div>
                    @endif
                </div>
            @endif
            @if($hasDraftChoices && !$exactPair)<p class="osds-muted">Counts and last-save information appear in the builder after you choose both drafts. No pair has been selected automatically.</p>@endif
            <details class="questionnaire-technical-details"><summary>Version history and technical details</summary><div class="questionnaire-version-grid"><div><h3>Questionnaire versions</h3>@forelse ($formVersions as $version)<p>{{ $version->definition->name }} &middot; v{{ $version->version_number }} &middot; {{ \App\Support\QuestionnairePresentation::statusLabel($version->state) }}</p>@empty<p>No questionnaire version exists.</p>@endforelse</div><div><h3>Document versions</h3>@forelse ($requirementVersions as $version)<p>Documents v{{ $version->version_number }} &middot; {{ \App\Support\QuestionnairePresentation::statusLabel($version->state) }}</p>@empty<p>No document version exists.</p>@endforelse</div></div></details>
        </section>
    @endif
</x-filament-panels::page>