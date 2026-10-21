<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Services\Authorization\InstitutionUpdateService;
use App\Support\StaffActionFeedback;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updated = null;
        try {
            StaffActionFeedback::run(function () use ($record, $data, &$updated): void {
                $updated = app(InstitutionUpdateService::class)->update(auth()->user(), $record, $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.name' => 'The institution update could not be confirmed. Keep your entries and inspect the saved institution before trying again.']);
        }

        $this->record = $updated;

        return $updated;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
