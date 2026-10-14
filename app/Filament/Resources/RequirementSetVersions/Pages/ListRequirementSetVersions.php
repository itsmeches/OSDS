<?php

namespace App\Filament\Resources\RequirementSetVersions\Pages;

use App\Filament\Resources\RequirementSetVersions\RequirementSetVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequirementSetVersions extends ListRecords
{
    protected static string $resource = RequirementSetVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
