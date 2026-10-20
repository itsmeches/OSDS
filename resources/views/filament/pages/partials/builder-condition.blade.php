@if($this->conditionIsProtected($item, $domain))
    <p role="note">This condition uses a rule structure or comparison this simple editor cannot edit. All rules are preserved when you save. Remove the condition only if you intend to replace the entire rule.</p>
    @include('filament.pages.partials.builder-condition-summary', ['rules'=>$item['condition_rules']])
@else
    <div class="builder-condition">
        <label>Show when<select wire:model.live="{{ $path }}.condition_source"><option value="">Always applicable</option>@foreach($sources as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
        <label>Comparison<select wire:model.live="{{ $path }}.condition_operator"><option value="equals">is</option><option value="not_equals">is not</option><option value="is_empty">is empty</option><option value="is_not_empty">is not empty</option></select></label>
        @if(!in_array($item['condition_operator'], ['is_empty','is_not_empty'], true))
            @php($choices = $this->sourceOptions($item['condition_source']))
            <label>Answer @if($choices)<select wire:model.live="{{ $path }}.condition_value"><option value="">Choose an answer</option>@foreach($choices as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>@else<input wire:model.live="{{ $path }}.condition_value">@endif</label>
        @endif
    </div>
@endif
@if($item['condition_rules'] || $item['condition_source'])
    <button type="button" wire:click="clearCondition('{{ $domain }}','{{ $item['key'] }}')" wire:confirm="Remove this entire condition? This item will always apply until you add another condition and save.">Remove condition</button>
@endif
