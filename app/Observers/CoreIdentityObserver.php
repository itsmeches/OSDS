<?php

namespace App\Observers;

use App\Models\Institution;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Database\Eloquent\Model;

class CoreIdentityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    private function record(Model $model, string $verb): void
    {
        $regionId = $model instanceof Institution ? $model->primary_region_id : ($model->region_id ?? null);
        $institutionId = $model instanceof Institution ? $model->id : ($model->institution_id ?? null);

        app(AuditRecorder::class)->record(
            action: $model->getMorphClass().'.'.$verb,
            outcome: 'success',
            actor: auth()->user(),
            resource: $model,
            regionId: $regionId,
            institutionId: $institutionId,
            metadata: ['changed_fields' => array_keys($model->getChanges())],
        );
    }
}
