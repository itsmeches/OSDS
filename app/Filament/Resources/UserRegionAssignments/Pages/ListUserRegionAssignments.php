<?php

namespace App\Filament\Resources\UserRegionAssignments\Pages;

use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserRegionAssignments extends ListRecords
{
    protected static string $resource = UserRegionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
