<?php

namespace App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages;

use App\Filament\Resources\ApplicantSandboxEligibilityGrants\ApplicantSandboxEligibilityGrantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicantSandboxEligibilityGrant extends CreateRecord
{
    protected static string $resource = ApplicantSandboxEligibilityGrantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['granted_by'] = auth()->id();

        return $data;
    }
}
