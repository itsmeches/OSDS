<?php

namespace App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages;

use App\Filament\Resources\ApplicantSandboxEligibilityGrants\ApplicantSandboxEligibilityGrantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApplicantSandboxEligibilityGrants extends ListRecords
{
    protected static string $resource = ApplicantSandboxEligibilityGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
