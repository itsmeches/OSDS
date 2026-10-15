@props(['question', 'model' => null, 'disabled' => false])
@php
    $read = fn (string $key, mixed $default = null) => is_array($question) ? ($question[$key] ?? $default) : ($question->{$key} ?? $default);
    $options = collect($read('options', []));
    $optionRead = fn ($option, string $key) => is_array($option) ? ($option[$key] ?? null) : ($option->{$key} ?? null);
    $type = $read('type', 'short_text');
    $inputId = 'question-'.($read('key') ?: $read('id'));
@endphp
<fieldset class="questionnaire-field" @disabled($disabled)>
    <legend class="questionnaire-field-label">{{ $read('label') ?: 'Untitled question' }} @if($read('is_required'))<span class="questionnaire-required" aria-label="required">*</span>@endif</legend>
    @if($read('help_text'))<p class="questionnaire-help">{{ $read('help_text') }}</p>@endif
    @if($type === 'long_text')
        <textarea id="{{ $inputId }}" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled) rows="4"></textarea>
    @elseif($type === 'single_choice')
        <div class="questionnaire-choice-list">@foreach($options as $option)<label><input type="radio" value="{{ $optionRead($option, 'key') }}" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)> <span>{{ $optionRead($option, 'label') }}</span></label>@endforeach</div>
    @elseif($type === 'multiple_choice')
        <div class="questionnaire-choice-list">@foreach($options as $option)<label><input type="checkbox" value="{{ $optionRead($option, 'key') }}" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)> <span>{{ $optionRead($option, 'label') }}</span></label>@endforeach</div>
    @elseif($type === 'yes_no')
        <div class="questionnaire-choice-list questionnaire-choice-inline"><label><input type="radio" value="yes" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)> <span>Yes</span></label><label><input type="radio" value="no" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)> <span>No</span></label></div>
    @elseif($type === 'declaration')
        <label class="questionnaire-declaration"><input type="checkbox" value="yes" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)> <span>I confirm this declaration.</span></label>
    @elseif($type === 'date')
        <input id="{{ $inputId }}" type="date" @if($model) wire:model.live="{{ $model }}" @endif @disabled($disabled)>
    @elseif($type === 'integer')
        <input id="{{ $inputId }}" type="number" step="1" inputmode="numeric" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled)>
    @elseif($type === 'decimal')
        <input id="{{ $inputId }}" type="number" step="any" inputmode="decimal" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled)>
    @elseif($type === 'telephone')
        <input id="{{ $inputId }}" type="tel" autocomplete="tel" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled)>
    @elseif($type === 'email')
        <input id="{{ $inputId }}" type="email" autocomplete="email" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled)>
    @else
        <input id="{{ $inputId }}" type="text" @if($model) wire:model.live.debounce.500ms="{{ $model }}" @endif @disabled($disabled)>
    @endif
</fieldset>
