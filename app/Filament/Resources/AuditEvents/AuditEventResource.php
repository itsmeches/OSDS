<?php

namespace App\Filament\Resources\AuditEvents;

use App\Filament\Resources\AuditEvents\Pages\ListAuditEvents;
use App\Filament\Resources\AuditEvents\Schemas\AuditEventForm;
use App\Filament\Resources\AuditEvents\Tables\AuditEventsTable;
use App\Models\AuditEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Security and oversight';

    protected static ?string $navigationLabel = 'Audit history';

    public static function form(Schema $schema): Schema
    {
        return AuditEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditEventsTable::configure($table);
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
            'index' => ListAuditEvents::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $regionIds = auth()->user()?->regionAssignments()->whereNull('revoked_at')->pluck('region_id') ?? collect();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query->whereIn('region_id', $regionIds)
            ->orWhere(fn (Builder $unscoped) => $unscoped->whereNull('region_id')->whereNull('institution_id')->where('actor_id', auth()->id())));
    }
}
