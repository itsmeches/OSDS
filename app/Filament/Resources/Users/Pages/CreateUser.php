<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\Identity\AccountGovernance;
use App\Support\AccountRole;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AccountGovernance::class)->create(
            auth()->user(),
            AccountRole::from($data['account_role']),
            $data,
            isset($data['region_id']) ? (int) $data['region_id'] : null,
            isset($data['institution_id']) ? (int) $data['institution_id'] : null,
        );
    }
}
