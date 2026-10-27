<?php

namespace App\Filament\Resources\UserRegionAssignments;

use App\Filament\Resources\UserRegionAssignments\Pages\CreateUserRegionAssignment;
use App\Filament\Resources\UserRegionAssignments\Pages\EditUserRegionAssignment;
use App\Filament\Resources\UserRegionAssignments\Pages\ListUserRegionAssignments;
use App\Filament\Resources\UserRegionAssignments\Schemas\UserRegionAssignmentForm;
use App\Filament\Resources\UserRegionAssignments\Tables\UserRegionAssignmentsTable;
use App\Models\UserRegionAssignment;
use App\Services\Authorization\StaffListScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserRegionAssignmentResource extends Resource
{
    protected static ?string $model = UserRegionAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Institutions and Access';

    protected static ?string $navigationLabel = 'Regional assignments';

    protected static ?string $modelLabel = 'regional assignment';

    protected static ?string $pluralModelLabel = 'regional assignments';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return UserRegionAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserRegionAssignmentsTable::configure($table);
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
            'index' => ListUserRegionAssignments::route('/'),
            'create' => CreateUserRegionAssignment::route('/create'),
            'edit' => EditUserRegionAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return app(StaffListScope::class)->regionAssignments(parent::getEloquentQuery(), auth()->user());
    }
}
