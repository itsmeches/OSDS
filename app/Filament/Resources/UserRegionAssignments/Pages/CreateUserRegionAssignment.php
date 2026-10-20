<?php

namespace App\Filament\Resources\UserRegionAssignments\Pages;

use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use App\Services\Authorization\RegionalGrantCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateUserRegionAssignment extends CreateRecord
{
    protected static string $resource = UserRegionAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$record): void {
                $record = app(RegionalGrantCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.starts_at' => 'The assignment could not be confirmed. Keep your entries and inspect saved assignments before trying again.']);
        }

        return $record;
    }
}
