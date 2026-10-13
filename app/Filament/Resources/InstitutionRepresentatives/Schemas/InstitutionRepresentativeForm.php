<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InstitutionRepresentativeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->relationship(
                    'user',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => $query->whereHas('regionAssignments', fn (Builder $assignments) => $assignments->whereIn('region_id', auth()->user()->regionAssignments()->whereNull('revoked_at')->pluck('region_id'))),
                )->searchable()->preload()->required(),
                Select::make('institution_id')->relationship(
                    'institution',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => $query->whereIn('primary_region_id', auth()->user()->regionAssignments()->whereNull('revoked_at')->pluck('region_id')),
                )->searchable()->preload()->required(),
                DateTimePicker::make('starts_at')->required()->default(now()),
                DateTimePicker::make('ends_at')->after('starts_at'),
                DateTimePicker::make('revoked_at')->disabled(),
            ]);
    }
}
