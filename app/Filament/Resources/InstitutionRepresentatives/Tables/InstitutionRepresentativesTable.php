<?php

namespace App\Filament\Resources\InstitutionRepresentatives\Tables;

use App\Services\Identity\AccountGovernance;
use App\Support\GrantPresentation;
use App\Support\StaffActionFeedback;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstitutionRepresentativesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('institution.name')->searchable(),
                TextColumn::make('starts_at')->dateTime(),
                TextColumn::make('ends_at')->dateTime()->placeholder('No end date'),
                TextColumn::make('effective_state')->label('Access status')->badge()->getStateUsing(fn ($record): string => GrantPresentation::state($record))->color(fn (string $state): string => GrantPresentation::color($state)),
                TextColumn::make('revoked_at')->dateTime()->placeholder('Not revoked'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('verify_school')->label('Verify SCHOOL account')->icon('heroicon-o-check-badge')
                    ->visible(fn ($record): bool => app(AccountGovernance::class)->canVerifySchool(auth()->user()->fresh(), $record->user()->firstOrFail(), $record))
                    ->modalContent(view('filament.partials.staff-action-recovery-notice'))->requiresConfirmation()->modalHeading('Verify this SCHOOL representative?')
                    ->modalDescription('A different authorized staff member must verify this account when separation of duties is enabled. Verification does not grant questionnaire eligibility.')
                    ->action(function ($record): void {
                        if (StaffActionFeedback::run(fn () => app(AccountGovernance::class)->verifySchool(auth()->user()->fresh(), $record->user()->firstOrFail(), $record))) {
                            Notification::make()->title('SCHOOL account verified')->body('The account is active. Applicant access still requires synthetic sandbox setup and current representation and eligibility.')->success()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
