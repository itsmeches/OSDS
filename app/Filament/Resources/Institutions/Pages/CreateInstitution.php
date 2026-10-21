<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Services\Authorization\InstitutionCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateInstitution extends CreateRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $created = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$created): void {
                $created = app(InstitutionCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.name' => 'The institution creation could not be confirmed. Keep your entries and inspect saved institutions before trying again.']);
        }

        return $created;
    }
}
