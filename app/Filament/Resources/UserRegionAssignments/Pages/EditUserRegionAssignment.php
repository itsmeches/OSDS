<?php

namespace App\Filament\Resources\UserRegionAssignments\Pages;

use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use Filament\Resources\Pages\EditRecord;

class EditUserRegionAssignment extends EditRecord
{
    use Concerns\EditsDelegatedAssignment;

    protected static string $resource = UserRegionAssignmentResource::class;
}
