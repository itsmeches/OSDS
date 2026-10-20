<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Pages;

use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use App\Services\Authorization\RepresentationCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateInstitutionRepresentative extends CreateRecord
{
    protected static string $resource = InstitutionRepresentativeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$record): void {
                $record = app(RepresentationCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.starts_at' => 'The representation could not be confirmed. Keep your entries and inspect saved representations before trying again.']);
        }

        return $record;
    }
}
