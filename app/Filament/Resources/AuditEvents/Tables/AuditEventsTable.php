<?php

namespace App\Filament\Resources\AuditEvents\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('action')->searchable(),
                TextColumn::make('actor.name')->label('Actor'),
                TextColumn::make('outcome')->badge(),
                TextColumn::make('correlation_id')->copyable()->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
