@if(isset($rules['all']) || isset($rules['any']))
    <p>{{ isset($rules['all']) ? 'All of these must match:' : 'At least one of these must match:' }}</p>
    <ul>@foreach(($rules['all'] ?? $rules['any']) as $child)<li>@include('filament.pages.partials.builder-condition-summary', ['rules'=>$child])</li>@endforeach</ul>
@else
    @php
        $source = collect($sections)->flatMap(fn($s)=>$s['questions'])->firstWhere('key', $rules['question'] ?? '');
        $comparison = ['equals'=>'is', 'not_equals'=>'is not', 'in'=>'is one of', 'not_in'=>'is not one of', 'is_empty'=>'is empty', 'is_not_empty'=>'is not empty'][$rules['operator'] ?? ''] ?? 'matches';
        $values = array_key_exists('value', $rules) ? (is_array($rules['value']) ? $rules['value'] : [$rules['value']]) : [];
        $labels = collect($source['options'] ?? [])->pluck('label','key');
    @endphp
    <span>{{ $source['label'] ?? 'Missing source question — correct before saving' }} {{ $comparison }} {{ collect($values)->map(fn($value)=>$value === null ? 'Not answered' : ($value === '' ? 'Blank text' : ($labels[$value] ?? (is_bool($value) ? ($value ? 'Yes' : 'No') : $value))))->implode(', ') }}</span>
@endif
