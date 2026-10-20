<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Identity\AccountGovernance;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditUser extends EditRecord
{
    use Concerns\HandlesAccountWrites;

    protected static string $resource = UserResource::class;

    protected function authorizeAccess(): void
    {
        $actor = User::query()->findOrFail(auth()->id());
        abort_unless(Gate::forUser($actor)->allows('update', $this->getRecord()->fresh()), 403);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_intersect_key($data, array_flip(['name', 'email', 'status', 'is_synthetic']));
    }

    protected function beforeValidate(): void
    {
        $actor = User::query()->findOrFail(auth()->id());
        $target = $this->getRecord()->fresh();
        foreach (['status', 'is_synthetic', 'deactivated_at', 'deactivated_by', 'email_verified_at', 'remember_token'] as $field) {
            // Hidden create-only fields can have null placeholders in a whole-form
            // Livewire update. They are never persisted by the edit schema/service.
            abort_if(isset($this->data[$field]) && $this->data[$field] != $target->getAttribute($field), 403);
        }
        foreach (['account_role', 'roles', 'region_id', 'institution_id'] as $field) {
            abort_if(filled($this->data[$field] ?? null), 403);
        }
        if (($this->data['email'] ?? $target->email) !== $target->email || filled($this->data['password'] ?? null)) {
            abort_unless(Gate::forUser($actor)->allows('updateCredentials', $target), 403);
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->accountWrite(fn () => app(AccountGovernance::class)->updateProfile(auth()->user(), $record, $data));
    }

    public function dehydrate(): void
    {
        unset($this->data['password']);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
