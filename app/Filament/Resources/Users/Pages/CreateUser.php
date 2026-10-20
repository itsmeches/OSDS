<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\Identity\AccountGovernance;
use App\Support\AccountRole;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    use Concerns\HandlesAccountWrites;

    protected static string $resource = UserResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Account created')->body(
            $this->getRecord()->hasRole(AccountRole::School->value)
                ? 'A different authorized reviewer can verify this SCHOOL account from Institution representatives. Creating an account does not grant questionnaire eligibility.'
                : 'The account has been saved with its selected scope. Existing delegated-management rules still apply.'
        );
    }

    public function dehydrate(): void
    {
        unset($this->data['password']);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->accountWrite(fn () => app(AccountGovernance::class)->create(
            auth()->user(),
            AccountRole::from($data['account_role']),
            $data,
            isset($data['region_id']) ? (int) $data['region_id'] : null,
            isset($data['institution_id']) ? (int) $data['institution_id'] : null,
        ));
    }
}
