@props(['records', 'evidence' => false, 'options'])
<section class="intake-panel" aria-label="Assigned queue">
    <form class="staff-queue-filters" wire:submit="$refresh">
        <label class="intake-field"><span>Search organization</span><input wire:model="search" type="search" maxlength="100" placeholder="Organization name"></label>
        <details class="staff-queue-advanced"><summary>Filter by service, region or status</summary><div class="staff-queue-filters">
        <label class="intake-field"><span>Service</span><select aria-label="Service" wire:model="service"><option value="">All assigned services</option>@foreach($options['services'] as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></label>
        <label class="intake-field"><span>Region</span><select aria-label="Region" wire:model="region"><option value="">All assigned regions</option>@foreach($options['regions'] as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach</select></label>
        <label class="intake-field"><span>Status</span><select aria-label="Status" wire:model="status"><option value="">All statuses</option>@if($evidence)<option value="current">Current test copy</option><option value="superseded">Earlier test copy</option>@else<option value="submitted">Ready for screening</option><option value="resubmitted">Corrections received</option><option value="under_screening">Screening in progress</option><option value="correction_requested">Waiting for applicant</option>@endif</select></label>
        </div></details>
        <div class="staff-queue-actions"><button class="intake-primary-action" type="submit" wire:loading.attr="disabled">Apply filters</button><button type="button" wire:click="clearFilters" wire:loading.attr="disabled">Clear filters</button><button type="button" wire:click="$refresh" wire:loading.attr="disabled">Refresh queue</button></div>
    </form>
    <p role="status" aria-live="polite"><span wire:loading.remove>{{ $records->total() }} {{ $evidence ? 'test reviews' : 'applications' }} found</span><span wire:loading>Updating queue…</span></p>
    <p class="staff-queue-hint">Scroll the table sideways to see all details and actions.</p>
    <div class="staff-queue-table" tabindex="0" role="region" aria-label="Assigned results">
        <table><caption class="sr-only">{{ $evidence ? 'Assigned document test reviews' : 'Assigned synthetic applications' }}</caption><thead><tr><th scope="col">Organization</th><th scope="col">Service / region</th><th scope="col">Status</th><th scope="col">{{ $evidence ? 'Sent' : 'Updated' }}</th><th scope="col">Next action</th></tr></thead><tbody>
        @forelse($records as $record)
            <tr wire:key="queue-{{ $record->public_id }}"><th scope="row">{{ $record->institution->display_name ?: $record->institution->name }}</th><td>{{ $record->module->name }}<br><small>{{ $record->region->name }}</small></td><td>@if($evidence){{ $record->newer_copies_exists ? 'Earlier test copy' : 'Current test copy' }}@else{{ match($record->state) { 'submitted'=>'Ready for screening', 'resubmitted'=>'Corrections received', 'under_screening'=>'Screening in progress', 'correction_requested'=>'Waiting for applicant', default=>'View application' } }}@endif</td><td><time>{{ ($evidence ? $record->created_at : $record->updated_at)->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time></td><td><a class="intake-primary-action" href="{{ $evidence ? \App\Filament\Pages\SyntheticEvidenceReviews::getUrl(['case'=>$record->public_id]) : \App\Filament\Pages\SyntheticApplicationIntake::getUrl(['application'=>$record->public_id]) }}">{{ $evidence ? 'Open test review' : 'Open application' }}<span class="sr-only">: {{ $record->institution->name }}</span></a></td></tr>
        @empty
            <tr><td colspan="5"><div class="intake-empty"><strong>No matching assigned {{ $evidence ? 'test reviews' : 'applications' }}</strong><p>Try clearing filters or refresh later. Only cases covered by your current service and regional assignments appear here.</p></div></td></tr>
        @endforelse
        </tbody></table>
    </div>
    @if($records->hasPages())
    <nav class="staff-queue-pager" aria-label="Results pages">
        <button type="button" wire:click="previousPage" wire:loading.attr="disabled" @disabled($records->onFirstPage())>Previous page</button>
        <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
        <button type="button" wire:click="nextPage" wire:loading.attr="disabled" @disabled(! $records->hasMorePages())>Next page</button>
    </nav>
    @endif
</section>
