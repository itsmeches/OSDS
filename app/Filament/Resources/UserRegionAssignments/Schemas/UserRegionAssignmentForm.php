<?php

namespace App\Filament\Resources\UserRegionAssignments\Schemas;

use App\Services\Authorization\StaffListScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserRegionAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->relationship(
                    'user',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => app(StaffListScope::class)->users($query, auth()->user(), 'region_scopes.assign'),
                )->getOptionLabelUsing(fn (Select $component, string $operation, ?Model $record): ?string => $operation === 'edit' && $record
                    ? $record->user()->value('name') : $component->getSelectedRecord()?->name)
                    ->searchable()->preload()->required()->disabledOn('edit'),
                Select::make('region_id')->relationship(
                    'region',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => app(StaffListScope::class)->regions($query, auth()->user(), 'region_scopes.assign'),
                )->searchable()->preload()->required()->disabledOn('edit'),
                DateTimePicker::make('starts_at')->label('Access starts')->required()->default(now())->helperText('Dates are shown in UTC. Editing dates does not transfer this assignment.')
                    ->disabled(fn (string $operation, ?Model $record): bool => $operation === 'edit' && ! auth()->user()?->can('update', $record)),
                DateTimePicker::make('ends_at')->label('Access ends')->after('starts_at')->helperText('Optional. Leave blank for no scheduled end.')
                    ->disabled(fn (string $operation, ?Model $record): bool => $operation === 'edit' && ! auth()->user()?->can('update', $record)),
                DateTimePicker::make('revoked_at')->label('Revoked on')->disabled(),
            ]);
    }
}
