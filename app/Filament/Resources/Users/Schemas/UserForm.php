<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Authorization\StaffListScope;
use App\Support\AccountRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->options(fn (): array => Region::query()->whereIn('id', app(ScopeAuthorizer::class)->effectiveRegionIds(auth()->user()))->orderBy('name')
                        ->pluck('name', 'id')->all())
                    ->visible(fn ($get, string $operation): bool => $operation === 'create' && filled($get('account_role')) && $get('account_role') !== AccountRole::School->value)
                    ->dehydrated(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') !== AccountRole::School->value)
                    ->required(fn ($get, string $operation): bool => $operation === 'create' && filled($get('account_role')) && $get('account_role') !== AccountRole::School->value),
                Select::make('institution_id')
                    ->label('Represented institution')
                    ->options(fn (): array => app(StaffListScope::class)->institutions(Institution::query(), auth()->user(), 'accounts.create.school')->orderBy('name')
                        ->pluck('name', 'id')->all())
                    ->visible(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value)
                    ->dehydrated(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value)
                    ->required(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value),
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->disabled(fn (string $operation, ?User $record): bool => $operation === 'edit' && ! auth()->user()?->can('updateCredentials', $record))
                    ->unique(ignoreRecord: true)
                    ->helperText('Each account must use a different email address.')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable(fn (string $operation): bool => $operation === 'edit')
                    ->disabled(fn (string $operation, ?User $record): bool => $operation === 'edit' && ! auth()->user()?->can('updateCredentials', $record))
                    ->helperText(fn (string $operation): string => $operation === 'edit' ? 'Leave blank to keep the current password. Re-enter after a validation error; passwords are not returned to the browser.' : 'Enter the password last. Re-enter after changing account type or correcting the form.')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Toggle::make('is_synthetic')
                    ->label('Synthetic applicant sandbox account')
                    ->helperText('Enable only for nonofficial sandbox testing. The account still requires verification before sign-in.')
                    ->default(fn (): bool => app(ApplicantSandboxGuard::class)->allowed())
                    ->visible(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value && app(ApplicantSandboxGuard::class)->allowed())
                    ->dehydrated(fn ($get, string $operation): bool => $operation === 'create' && $get('account_role') === AccountRole::School->value && app(ApplicantSandboxGuard::class)->allowed()),
                Select::make('status')
                    ->options(['pending_verification' => 'Pending verification', 'active' => 'Active', 'suspended' => 'Suspended', 'inactive' => 'Inactive'])
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }
}
