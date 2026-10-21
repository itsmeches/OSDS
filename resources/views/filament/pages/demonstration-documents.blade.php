<x-filament-panels::page>
    <x-filament::section heading="Applicant issuance form settings" description="Publish an editable demonstration form for signed-in applicants. This does not establish eligibility or official issuance.">
        <p>Customize labels and instructions, add up to 12 questions, and enable or withdraw applicant access. Certificate merge fields stay required. Additional answers print in a separate demonstration appendix; they cannot change certificate type, director or validity.</p>
        <form wire:submit="publishIssuanceForm" class="downloads-form">
            <div class="downloads-field"><label for="issuance-config-title">Applicant form title</label><x-filament::input.wrapper><x-filament::input id="issuance-config-title" wire:model="issuanceTitle" required maxlength="160" /></x-filament::input.wrapper>@error('issuanceTitle')<p role="alert">{{ $message }}</p>@enderror @error('title')<p role="alert">{{ $message }}</p>@enderror</div>
            <div class="downloads-field"><label><input type="checkbox" wire:model="issuanceEnabled"> Available to authorized applicants</label><p class="downloads-help">Uncheck and save to withdraw the form. Earlier definitions remain in history.</p></div>
            <div class="downloads-field downloads-field-wide"><label for="issuance-config-instructions">Applicant instructions</label><textarea id="issuance-config-instructions" wire:model="issuanceInstructions" rows="3" maxlength="2000"></textarea>@error('instructions')<p role="alert">{{ $message }}</p>@enderror</div>
            @foreach(\App\Services\Documents\DemoIssuanceFormService::LABELS as $key => $default)
                <div class="downloads-field"><label for="issuance-label-{{ $key }}">Label for {{ $default }}</label><x-filament::input.wrapper><x-filament::input id="issuance-label-{{ $key }}" wire:model="issuanceLabels.{{ $key }}" required maxlength="160" /></x-filament::input.wrapper>@error('labels.'.$key)<p role="alert">{{ $message }}</p>@enderror</div>
            @endforeach
            @foreach($issuanceExtras as $index => $field)
                <fieldset class="osds-workflow-card downloads-field-wide" wire:key="issuance-extra-{{ $field['key'] }}"><legend>Additional question {{ $index + 1 }}</legend><div class="downloads-form">
                    <div class="downloads-field"><label for="extra-label-{{ $index }}">Question label {{ $index + 1 }}</label><x-filament::input.wrapper><x-filament::input id="extra-label-{{ $index }}" wire:model="issuanceExtras.{{ $index }}.label" required maxlength="160" /></x-filament::input.wrapper>@error('extras.'.$index.'.label')<p role="alert">{{ $message }}</p>@enderror</div>
                    <div class="downloads-field"><label for="extra-type-{{ $index }}">Answer type {{ $index + 1 }}</label><x-filament::input.wrapper><x-filament::input.select id="extra-type-{{ $index }}" wire:model="issuanceExtras.{{ $index }}.type"><option value="text">Short text</option><option value="textarea">Long text</option><option value="date">Date</option></x-filament::input.select></x-filament::input.wrapper></div>
                    <label><input type="checkbox" wire:model="issuanceExtras.{{ $index }}.required"> Required answer {{ $index + 1 }}</label>
                    <x-filament::button color="gray" wire:click="removeIssuanceQuestion({{ $index }})">Remove question {{ $index + 1 }}</x-filament::button>
                </div></fieldset>
            @endforeach
            @if($errors->has('extras'))<p role="alert">{{ $errors->first('extras') }}</p>@endif
            <div class="downloads-actions downloads-field-wide"><x-filament::button type="button" color="gray" wire:click="addIssuanceQuestion">Add question</x-filament::button><x-filament::button type="submit" wire:loading.attr="disabled">Save applicant form</x-filament::button><span class="downloads-help">{{ $expectedForm ? 'Saved form revision '.$expectedForm : 'Not yet published' }}</span></div>
        </form>
    </x-filament::section>
    <x-filament::section heading="Issuance form — demonstration preview" description="Enter fictional applicant details. Select an office to fill its saved director and designation. This does not approve or issue a certificate.">
        @if($directory)
            <form method="POST" action="{{ route('documents.demonstration.issuance') }}" target="_blank" class="downloads-form" wire:key="issuance-{{ $directory->id }}" x-data="{ offices: @js($directory->offices), selected: '' }">
                @csrf
                <input type="hidden" name="directory_revision" value="{{ $directory->id }}">
                <div class="downloads-field"><label for="issuance-kind">Type of certificate (required)</label><x-filament::input.wrapper><x-filament::input.select id="issuance-kind" name="kind" required><option value="cor">Certificate of Registration (terminology pending)</option><option value="acsa">Authority to Conduct Sports Activity</option></x-filament::input.select></x-filament::input.wrapper></div>
                <div class="downloads-field"><label for="issuance-organization">Name of HEI / Association (fictional, required)</label><x-filament::input.wrapper><x-filament::input id="issuance-organization" name="organization" required maxlength="200" /></x-filament::input.wrapper></div>
                <div class="downloads-field downloads-field-wide"><label for="issuance-address">Address (fictional, required)</label><textarea id="issuance-address" name="address" required maxlength="400" rows="2"></textarea></div>
                <div class="downloads-field"><label for="issuance-date">Demonstration date (required)</label><x-filament::input.wrapper><x-filament::input id="issuance-date" name="date" type="date" required /></x-filament::input.wrapper></div>
                <div class="downloads-field"><label for="issuance-office">CHED Regional Office (required)</label><x-filament::input.wrapper><x-filament::input.select id="issuance-office" name="office_key" required x-model="selected"><option value="">Select an office</option>@foreach($directory->offices as $key => $office)<option value="{{ $key }}">{{ $office['label'] }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div class="downloads-field"><label for="resolved-director">Director (from office directory)</label><x-filament::input.wrapper><x-filament::input id="resolved-director" readonly x-bind:value="offices[selected]?.director ?? ''" /></x-filament::input.wrapper></div>
                <div class="downloads-field"><label for="resolved-designation">Designation (from office directory)</label><x-filament::input.wrapper><x-filament::input id="resolved-designation" readonly x-bind:value="offices[selected]?.designation ?? ''" /></x-filament::input.wrapper></div>
                <div class="downloads-field downloads-field-wide"><label><input type="checkbox" name="fictional_confirmation" value="1" required> I am using fictional applicant details for a nonofficial demonstration only.</label><p class="downloads-help">Directory revision {{ $directory->id }}. Preview opens in a new tab; use Print / Save as PDF. No email is sent, official serial allocated or legal validity assigned.</p></div>
                <div class="downloads-field-wide"><x-filament::button type="submit">Preview filled document (new tab)</x-filament::button></div>
            </form>
        @else
            <p>Add the first demonstration office below to enable the issuance preview. Real personnel data is not automatically imported or certified.</p>
        @endif
    </x-filament::section>
    <x-filament::section heading="Demonstration regional-office directory" description="OSDS maintains these display mappings. A director name does not grant application authority. Saving keeps earlier directory revisions.">
        <div class="downloads-form">
            @foreach($directory?->offices ?? [] as $key => $office)
                <article class="osds-workflow-card"><h3>{{ $office['label'] }}</h3><p>{{ $office['director'] }} · {{ $office['designation'] }}</p><x-filament::button color="gray" wire:click="editOffice('{{ $key }}')" :aria-label="'Edit office: '.$office['label']">Edit office</x-filament::button></article>
            @endforeach
        </div>
        <form wire:submit="saveOffice" class="downloads-form">
            <div class="downloads-field"><label for="office-label">Office label (required)</label><x-filament::input.wrapper><x-filament::input id="office-label" wire:model="officeLabel" required maxlength="100" placeholder="e.g. CHEDRO I — demonstration" /></x-filament::input.wrapper>@error('officeLabel')<p role="alert">{{ $message }}</p>@enderror @error('label')<p role="alert">{{ $message }}</p>@enderror</div>
            <div class="downloads-field"><label for="office-director">Director name (required)</label><x-filament::input.wrapper><x-filament::input id="office-director" wire:model="director" required maxlength="160" /></x-filament::input.wrapper>@error('director')<p role="alert">{{ $message }}</p>@enderror</div>
            <div class="downloads-field"><label for="office-designation">Designation (required)</label><x-filament::input.wrapper><x-filament::input id="office-designation" wire:model="designation" required maxlength="160" /></x-filament::input.wrapper>@error('designation')<p role="alert">{{ $message }}</p>@enderror</div>
            <div class="downloads-editor-footer downloads-field-wide"><p class="downloads-help">{{ $officeKey ? 'Save creates a new directory revision. Previously downloaded previews keep their embedded names.' : 'New entry; use fictional personnel unless OSDS has confirmed the supplied names for demonstration use.' }}</p><div class="downloads-actions"><x-filament::button type="submit" wire:loading.attr="disabled">{{ $officeKey ? 'Save office revision' : 'Add demonstration office' }}</x-filament::button><x-filament::button color="gray" wire:click="newOffice">Clear / add another</x-filament::button></div></div>
        </form>
    </x-filament::section>
    <x-filament::section heading="Certificate layout previews" description="Original fixed samples retained for comparison. Use the issuance form above for the dynamic demo-v2 preview.">
        <p>These original samples use draft template version demo-v1. Wording, COR terminology and validity treatment require OSDS confirmation. A downloaded HTML preview includes its watermark and can be printed or saved as PDF using your browser.</p>
    </x-filament::section>
    @foreach(['cor' => 'Certificate of Registration — source title pending confirmation', 'acsa' => 'Authority to Conduct Sports Activity'] as $kind => $label)
        <x-filament::section :heading="$label" description="DRAFT / DEMONSTRATION ONLY · demo-v1 · Fixed fictional merge data">
            <div class="downloads-actions">
                <x-filament::button tag="a" :href="route('documents.demonstration.preview', ['version' => 'demo-v1', 'kind' => $kind])" target="_blank" rel="noopener" :aria-label="'Preview layout: '.$label.' (opens a new tab)'">Preview layout</x-filament::button>
                <x-filament::button tag="a" color="gray" :href="route('documents.demonstration.preview', ['version' => 'demo-v1', 'kind' => $kind, 'download' => 1])" :aria-label="'Download HTML preview: '.$label">Download HTML preview</x-filament::button>
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
