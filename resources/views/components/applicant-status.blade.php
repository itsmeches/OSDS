@props(['state'])
@php($labels = ['draft' => 'Draft — not sent', 'submitted' => 'Sent — waiting for screening', 'under_screening' => 'Being screened', 'correction_requested' => 'Your corrections are needed', 'resubmitted' => 'Corrections sent — waiting for screening'])
<span class="status">{{ $labels[$state] ?? str($state)->replace('_', ' ')->title() }}</span>
