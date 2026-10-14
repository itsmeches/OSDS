<?php

namespace App\Filament\Resources\EvidenceProviderTypes;

use App\Filament\Resources\EvidenceProviderTypes\Pages\ListEvidenceProviderTypes;
use App\Models\EvidenceProviderType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EvidenceProviderTypeResource extends Resource
{
    protected static ?string $model = EvidenceProviderType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('questionnaires.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->badge(), TextColumn::make('name'), IconColumn::make('is_enabled')->boolean()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListEvidenceProviderTypes::route('/')];
    }
}
