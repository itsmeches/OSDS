<?php

namespace App\Filament\Resources\UserRegionAssignments\Tables;

use App\Support\GrantPresentation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserRegionAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('region.name')->searchable(),
                TextColumn::make('starts_at')->dateTime(),
                TextColumn::make('ends_at')->dateTime()->placeholder('No end date'),
                TextColumn::make('effective_state')->label('Access status')->badge()->getStateUsing(fn ($record): string => GrantPresentation::state($record))->color(fn (string $state): string => GrantPresentation::color($state)), TextColumn::make('revoked_at')->dateTime()->placeholder('Not revoked'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
