<?php

namespace App\Filament\Resources\FormDefinitions;

use App\Filament\Resources\FormDefinitions\Pages\CreateFormDefinition;
use App\Filament\Resources\FormDefinitions\Pages\EditFormDefinition;
use App\Filament\Resources\FormDefinitions\Pages\ListFormDefinitions;
use App\Models\FormDefinition;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormDefinitionResource extends Resource
{
    protected static ?string $model = FormDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('module_id')->relationship('module', 'name')->required(),
            TextInput::make('key')->required()->alphaDash()->maxLength(100),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('audience')->required()->default('applicant')->maxLength(40),
            Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('module.name')->badge(), TextColumn::make('key')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('audience')->badge()])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListFormDefinitions::route('/'), 'create' => CreateFormDefinition::route('/create'), 'edit' => EditFormDefinition::route('/{record}/edit')];
    }
}
