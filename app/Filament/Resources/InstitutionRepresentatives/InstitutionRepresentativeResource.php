<?php

namespace App\Filament\Resources\InstitutionRepresentatives;

use App\Filament\Resources\InstitutionRepresentatives\Pages\CreateInstitutionRepresentative;
use App\Filament\Resources\InstitutionRepresentatives\Pages\EditInstitutionRepresentative;
use App\Filament\Resources\InstitutionRepresentatives\Pages\ListInstitutionRepresentatives;
use App\Filament\Resources\InstitutionRepresentatives\Schemas\InstitutionRepresentativeForm;
use App\Filament\Resources\InstitutionRepresentatives\Tables\InstitutionRepresentativesTable;
use App\Models\InstitutionRepresentative;
use App\Services\Authorization\StaffListScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstitutionRepresentativeResource extends Resource
{
    protected static ?string $model = InstitutionRepresentative::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Institutions and Access';

    protected static ?string $navigationLabel = 'Institution representatives';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return InstitutionRepresentativeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstitutionRepresentativesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstitutionRepresentatives::route('/'),
            'create' => CreateInstitutionRepresentative::route('/create'),
            'edit' => EditInstitutionRepresentative::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return app(StaffListScope::class)->representatives(parent::getEloquentQuery(), auth()->user());
    }
}
