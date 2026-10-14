<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Institution;
use App\Models\Region;
use App\Services\Authorization\ScopeAuthorizer;
use App\Support\AccountRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('account_role')
                    ->label('Account type')
                    ->options(fn (): array => collect(AccountRole::cases())
                        ->filter(function (AccountRole $role): bool {
                            $permission = config("account-governance.creation_permissions.{$role->value}");

                            return is_string($permission) && auth()->user()?->can($permission);
                        })->mapWithKeys(fn (AccountRole $role) => [$role->value => $role->value])->all())
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->live(),
                Select::make('region_id')
                    ->label('Authorized region')
                    ->options(fn (): array => Region::query()->orderBy('name')->get()
                        ->filter(fn (Region $region) => app(ScopeAuthorizer::class)->canAccessRegion(auth()->user(), $region->id))
                        ->pluck('name', 'id')->all())
                    ->visible(fn ($get, string $operation): bool => $operation === 'create' && filled($get('account_role')) && $get('account_role') !== AccountRole::School->value)
                    ->dehydrated(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') !== AccountRole::School->value)
                    ->required(fn ($get, string $operation): bool => $operation === 'create' && filled($get('account_role')) && $get('account_role') !== AccountRole::School->value),
                Select::make('institution_id')
                    ->label('Verified institution')
                    ->options(fn (): array => Institution::query()->orderBy('name')->get()
                        ->filter(fn (Institution $institution) => app(ScopeAuthorizer::class)->canAccessInstitution(auth()->user(), $institution))
                        ->pluck('name', 'id')->all())
                    ->visible(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value)
                    ->dehydrated(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value)
                    ->required(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value),
                Select::make('status')
                    ->options(['pending_verification' => 'Pending verification', 'active' => 'Active', 'suspended' => 'Suspended', 'inactive' => 'Inactive'])
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }
}
