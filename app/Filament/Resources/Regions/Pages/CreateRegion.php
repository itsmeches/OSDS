<?php

namespace App\Filament\Resources\Regions\Pages;

use App\Filament\Resources\Regions\RegionResource;
use App\Services\Authorization\RegionCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $created = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$created): void {
                $created = app(RegionCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.name' => 'The region creation could not be confirmed. Keep your entries and inspect saved regions before trying again.']);
        }

        return $created;
    }
}
