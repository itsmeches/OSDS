<?php

namespace App\Filament\Resources\Regions\Pages;

use App\Filament\Resources\Regions\RegionResource;
use App\Services\Authorization\RegionUpdateService;
use App\Support\StaffActionFeedback;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updated = null;
        try {
            StaffActionFeedback::run(function () use ($record, $data, &$updated): void {
                $updated = app(RegionUpdateService::class)->update(auth()->user(), $record, $data);
            });
        } catch (Halt) {
            throw ValidationException::withMessages(['data.name' => 'The region update could not be confirmed. Keep your entries and inspect the saved region before trying again.']);
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
