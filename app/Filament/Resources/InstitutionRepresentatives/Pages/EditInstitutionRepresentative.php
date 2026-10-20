<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Pages;

use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use App\Filament\Resources\UserRegionAssignments\Pages\Concerns\EditsDelegatedAssignment;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionRepresentative extends EditRecord
{
    use EditsDelegatedAssignment;

    protected static string $resource = InstitutionRepresentativeResource::class;
}
