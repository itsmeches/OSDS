@props(['question', 'value'])
@php
    $labels = $question->options->pluck('label', 'key');
    $display = match ($question->type) {
        'single_choice' => $labels[$value ?? ''] ?? 'Not answered',
        'multiple_choice' => collect(is_array($value) ? $value : [])->map(fn ($key) => $labels[$key] ?? 'Unknown option')->implode(', '),
        'yes_no' => match ($value) { 'yes' => 'Yes', 'no' => 'No', default => 'Not answered' },
        'declaration' => $value === true || $value === 'yes' ? 'Confirmed' : 'Not confirmed',
        default => is_scalar($value) ? (string) $value : '',
    };
@endphp
<span>{{ $display !== '' ? $display : 'Not answered' }}</span>
