<?php

namespace App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages;

use App\Filament\Resources\SyntheticApplicationReviewerAssignments\SyntheticApplicationReviewerAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSyntheticApplicationReviewerAssignments extends ListRecords
{
    protected static string $resource = SyntheticApplicationReviewerAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
