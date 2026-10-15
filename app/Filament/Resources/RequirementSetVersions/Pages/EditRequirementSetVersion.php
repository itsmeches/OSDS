<?php

namespace App\Filament\Resources\RequirementSetVersions\Pages;

use App\Filament\Resources\RequirementSetVersions\RequirementSetVersionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditRequirementSetVersion extends EditRecord
{
    protected static string $resource = RequirementSetVersionResource::class;

    public function getTitle(): string
    {
        return 'Edit document requirements';
    }

    public function getSubheading(): string
    {
        return 'Configure required documents, accepted link types, evidence counts, instructions, and conditional applicability.';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Save draft');
    }
}
