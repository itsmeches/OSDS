<?php

namespace App\Filament\Resources\UserRegionAssignments\Pages;

use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserRegionAssignment extends EditRecord
{
    protected static string $resource = UserRegionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
