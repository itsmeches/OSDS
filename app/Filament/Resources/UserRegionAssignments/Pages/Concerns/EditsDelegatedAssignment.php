<?php

namespace App\Filament\Resources\UserRegionAssignments\Pages\Concerns;

use App\Models\User;
use App\Services\Authorization\AccessGovernance;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

trait EditsDelegatedAssignment
{
    #[Locked]
    public string $assignmentVersion = '';

    public function getSubheading(): ?string
    {
        return Gate::forUser(User::query()->findOrFail(auth()->id()))->allows('update', $this->getRecord()->fresh())
            ? 'Edit the effective dates in UTC. The account and assigned location remain fixed.'
            : 'Read only. You can view this assignment, but you cannot change it here. Ask a staff member authorized to manage this access.';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->visible(fn (): bool => Gate::forUser(User::query()->findOrFail(auth()->id()))->allows('update', $this->getRecord()->fresh()));
    }

    protected function authorizeAccess(): void
    {
        // Viewing metadata is distinct from delegation. Every hydration repeats
        // this read check; the service separately authorizes the mutation.
        $actor = User::query()->findOrFail(auth()->id());
        abort_unless(Gate::forUser($actor)->allows('view', $this->getRecord()->fresh()), 403);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->assignmentVersion === '') {
            $this->assignmentVersion = app(AccessGovernance::class)->assignmentVersion($this->getRecord());
        }

        return array_intersect_key($data, array_flip(['user_id', 'region_id', 'institution_id', 'starts_at', 'ends_at', 'revoked_at']));
    }

    protected function beforeValidate(): void
    {
        // Validate raw state too: disabled/nondehydrated fields are not a security
        // boundary. This call authorizes before schema validation can hide them.
        $actor = User::query()->findOrFail(auth()->id());
        abort_unless(Gate::forUser($actor)->allows('update', $this->getRecord()->fresh()), 403);
        $record = $this->getRecord()->fresh();
        foreach ($this->data as $field => $value) {
            if (! in_array($field, ['starts_at', 'ends_at'], true)) {
                abort_unless(array_key_exists($field, $record->getAttributes()) && $value == $record->getRawOriginal($field), 403);
            }
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(AccessGovernance::class);
        $saved = $service->amendAssignment(auth()->user(), $record, $data, $this->assignmentVersion);
        // A later writer may commit immediately after the service releases its
        // lock. A fresh reread here could acknowledge that unseen newer version.
        $this->assignmentVersion = $service->assignmentVersion($saved);

        return $saved;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
