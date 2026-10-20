<?php

namespace App\Filament\Resources\Institutions\Schemas;

use App\Services\Authorization\StaffListScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InstitutionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('official_id')->maxLength(255)->unique(ignoreRecord: true),
                Select::make('primary_region_id')->relationship(
                    'primaryRegion',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => app(StaffListScope::class)->regions($query, auth()->user(), 'institutions.manage'),
                )->searchable()->preload(),
                Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->required(),
            ]);
    }
}
