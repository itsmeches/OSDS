<?php

namespace App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages;

use App\Filament\Resources\SyntheticApplicationReviewerAssignments\SyntheticApplicationReviewerAssignmentResource;
use App\Services\Applications\ApplicationReviewerCreationService;
use App\Support\StaffActionFeedback;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSyntheticApplicationReviewerAssignment extends CreateRecord
{
    protected static string $resource = SyntheticApplicationReviewerAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = null;
        try {
            StaffActionFeedback::run(function () use ($data, &$record): void {
                $record = app(ApplicationReviewerCreationService::class)->create(auth()->user(), $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.reason' => 'The assignment could not be confirmed. Keep your entries and inspect saved assignments before trying again.']);
        }

        return $record;
    }
}
