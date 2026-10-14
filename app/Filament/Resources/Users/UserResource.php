<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Services\Authorization\ScopeAuthorizer;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user !== null && app(ScopeAuthorizer::class)->hasNationwideAccess($user)) {
            return parent::getEloquentQuery();
        }
        $now = CarbonImmutable::now();
        $regionIds = $user?->regionAssignments()->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->pluck('region_id') ?? collect();

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query->whereKey($user?->id)
            ->orWhereHas('regionAssignments', fn (Builder $assignments) => $assignments->whereIn('region_id', $regionIds)
                ->whereNull('revoked_at')->where('starts_at', '<=', $now)
                ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>', $now))));
    }
}
