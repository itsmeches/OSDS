<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\InstitutionRepresentative;
use App\Services\Identity\AccountGovernance;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('Not verified')
                    ->sortable(),
                TextColumn::make('roles.name')->label('Account type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('is_synthetic')->label('Applicant sandbox')->badge()->formatStateUsing(fn (bool $state): string => $state ? 'Synthetic' : 'Not enabled')->color(fn (bool $state): string => $state ? 'info' : 'gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('enable_sandbox')
                    ->label('Enable applicant sandbox')
                    ->icon('heroicon-o-beaker')
                    ->requiresConfirmation()
                    ->modalDescription('This marks the pending SCHOOL account as synthetic for nonofficial sandbox testing. Verification is still required before sign-in.')
                    ->visible(function ($record): bool {
                        $representation = InstitutionRepresentative::query()->with('institution')->where('user_id', $record->id)->whereNull('revoked_at')->latest('id')->first();

                        return $representation && app(AccountGovernance::class)->canEnableSyntheticApplicant(auth()->user(), $record, $representation);
                    })
                    ->action(function ($record): void {
                        $representation = InstitutionRepresentative::query()->with('institution')->where('user_id', $record->id)->whereNull('revoked_at')->latest('id')->firstOrFail();
                        app(AccountGovernance::class)->enableSyntheticApplicant(auth()->user(), $record, $representation);
                        Notification::make()->title('Applicant sandbox enabled')->body('A different authorized CHEDRO reviewer must still verify this account.')->success()->send();
                    }),
                Action::make('verify_school')
                    ->label('Verify SCHOOL account')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->modalDescription('Verification activates this SCHOOL account and its represented institution. The creator cannot verify their own grant unless that separation rule is explicitly enabled.')
                    ->visible(function ($record): bool {
                        $representation = InstitutionRepresentative::query()->with('institution')->where('user_id', $record->id)->whereNull('revoked_at')->latest('id')->first();

                        return $representation && app(AccountGovernance::class)->canVerifySchool(auth()->user(), $record, $representation);
                    })
                    ->action(function ($record): void {
                        $representation = InstitutionRepresentative::query()->with('institution')->where('user_id', $record->id)->whereNull('revoked_at')->latest('id')->firstOrFail();
                        app(AccountGovernance::class)->verifySchool(auth()->user(), $record, $representation);
                        Notification::make()->title('SCHOOL account verified')->body('The account is active. Synthetic accounts can now use Applicant sign in.')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
