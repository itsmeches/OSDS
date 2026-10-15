@props(['requirement'])
@php($read = fn (string $key, mixed $default = null) => is_array($requirement) ? ($requirement[$key] ?? $default) : ($requirement->{$key} ?? $default))
<article class="questionnaire-requirement">
    <h4>{{ $read('label') ?: 'Untitled document' }} @if($read('is_required'))<span class="questionnaire-required" aria-label="required">*</span>@endif</h4>
    @if($read('instructions'))<p>{{ $read('instructions') }}</p>@endif
    <small>{{ $read('minimum_evidence', 0) }}–{{ $read('maximum_evidence', 1) }} document link(s)</small>
</article>
