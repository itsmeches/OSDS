<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Pages;

use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionRepresentative extends EditRecord
{
    protected static string $resource = InstitutionRepresentativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
