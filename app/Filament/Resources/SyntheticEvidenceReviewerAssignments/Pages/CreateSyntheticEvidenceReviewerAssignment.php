<?php

namespace App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages;

use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\SyntheticEvidenceReviewerAssignmentResource;
use App\Services\Evidence\EvidenceReviewerCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSyntheticEvidenceReviewerAssignment extends CreateRecord
{
    protected static string $resource = SyntheticEvidenceReviewerAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$record): void {
                $record = app(EvidenceReviewerCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.reason' => 'The assignment could not be confirmed. Keep your entries and inspect saved assignments before trying again.']);
        }

        return $record;
    }
}
