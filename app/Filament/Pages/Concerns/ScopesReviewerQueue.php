<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Application;
use App\Models\OsdsModule;
use App\Models\Region;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ScopesReviewerQueue
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $service = '';

    #[Url]
    public string $region = '';

    #[Url]
    public string $status = '';

    protected function reportStaffFailure(\Throwable $exception): void
    {
        if ($exception instanceof AuthorizationException || $exception instanceof HttpExceptionInterface || $exception instanceof ModelNotFoundException) {
            throw $exception;
        }
        $reference = (string) Str::uuid();
        Log::error('Staff action result unconfirmed', ['reference' => $reference, 'exception_class' => $exception::class]);
        $this->addError('staffAction', 'We could not confirm the result. Keep your input, then refresh and check the case history before repeating this action. Support reference: '.$reference);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'service', 'region', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'service', 'region', 'status']);
        $this->resetPage();
    }

    #[Computed]
    public function queueOptions(): array
    {
        $query = $this->queueQuery()->reorder();
        $regionColumn = $query->getModel() instanceof Application ? 'routing_region_id' : 'region_id';

        return [
            'services' => OsdsModule::query()->whereIn('id', (clone $query)->select('module_id'))->orderBy('name')->pluck('name', 'code'),
            'regions' => Region::query()->whereIn('id', (clone $query)->select($regionColumn))->orderBy('name')->pluck('name', 'code'),
        ];
    }

    protected function filterQueue(Builder $query, string $regionColumn): Builder
    {
        // This builder MUST already carry the workflow-specific ReviewerQueueScope.
        $term = mb_substr(trim($this->search), 0, 100);
        if ($term !== '') {
            $query->whereHas('institution', fn (Builder $org) => $org->where(function (Builder $names) use ($term) {
                $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $names->where('name', 'ilike', $pattern)->orWhere('display_name', 'ilike', $pattern);
            }));
        }
        if ($this->service !== '') {
            $query->whereHas('module', fn (Builder $module) => $module->where('code', $this->service));
        }
        if ($this->region !== '') {
            $query->whereHas('region', fn (Builder $region) => $region->where('code', $this->region));
        }

        return $query;
    }
}
