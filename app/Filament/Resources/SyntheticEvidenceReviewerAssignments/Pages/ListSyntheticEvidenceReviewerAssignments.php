<?php

namespace App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages;

use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\SyntheticEvidenceReviewerAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSyntheticEvidenceReviewerAssignments extends ListRecords
{
    protected static string $resource = SyntheticEvidenceReviewerAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
