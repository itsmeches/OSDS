<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Pages;

use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionRepresentatives extends ListRecords
{
    protected static string $resource = InstitutionRepresentativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
