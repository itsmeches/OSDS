<?php

namespace App\Filament\Resources\Regions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->required()->maxLength(32)->unique(ignoreRecord: true),
                TextInput::make('name')->required()->maxLength(255),
                Toggle::make('is_active')->required()->default(true),
            ]);
    }
}
