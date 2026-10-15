<?php

namespace App\Filament\Resources\FormVersions\Pages;

use App\Filament\Resources\FormVersions\FormVersionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditFormVersion extends EditRecord
{
    protected static string $resource = FormVersionResource::class;

    public function getTitle(): string
    {
        return 'Edit questionnaire draft';
    }

    public function getSubheading(): string
    {
        return 'Organize sections, questions, choices, response settings, and display conditions. Changes remain a draft until submitted for review.';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Save draft');
    }
}
