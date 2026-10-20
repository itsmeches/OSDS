<?php

namespace App\Filament\Resources\PublicationManifests;

use App\Filament\Resources\PublicationManifests\Pages\ListPublicationManifests;
use App\Models\PublicationManifest;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\QuestionnairePresentation;
use App\Support\StaffActionFeedback;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicationManifestResource extends Resource
{
    protected static ?string $model = PublicationManifest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Published questionnaires';

    protected static ?string $modelLabel = 'published questionnaire';

    protected static ?string $pluralModelLabel = 'published questionnaires';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('module.name')->label('Service')->weight('semibold')->searchable(),
                TextColumn::make('channel')->label('Audience')->badge()->formatStateUsing(fn (string $state): string => $state === 'applicant_sandbox' ? 'Synthetic applicants' : 'Staff preview'),
                TextColumn::make('publication_status')->label('Status')->badge()
                    ->getStateUsing(function (PublicationManifest $record): string {
                        if ($record->retirement !== null) {
                            return 'retired';
                        }
                        if ($record->channel === 'applicant_sandbox') {
                            if ($record->effective_from->isFuture()) {
                                return 'scheduled';
                            }
                            if ($record->effective_until !== null && ! $record->effective_until->isFuture()) {
                                return 'expired';
                            }
                        }

                        return 'published';
                    })
                    ->formatStateUsing(fn (?string $state, PublicationManifest $record): string => match ($state) {
                        'published' => $record->channel === 'applicant_sandbox' ? 'Available' : 'Published',
                        'scheduled' => 'Scheduled', 'expired' => 'Availability ended', default => QuestionnairePresentation::statusLabel($state),
                    })
                    ->color(fn (?string $state): string => $state === 'expired' ? 'warning' : QuestionnairePresentation::statusColor($state))
                    ->icon(fn (?string $state) => in_array($state, ['scheduled', 'expired'], true) ? Heroicon::OutlinedClock : QuestionnairePresentation::statusIcon($state)),
                TextColumn::make('published_at')->label('Published')->dateTime('M j, Y g:i A')->sortable(),
                TextColumn::make('effective_from')->label('Available from')->dateTime('M j, Y g:i A')->toggleable(),
                TextColumn::make('effective_until')->label('Available until')->dateTime('M j, Y g:i A')->placeholder('No end date')->toggleable(),
                TextColumn::make('combined_hash')->label('Configuration reference')->limit(12)->copyable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([SelectFilter::make('channel')->label('Audience')->options(['review_sandbox' => 'Staff preview', 'applicant_sandbox' => 'Synthetic applicants'])])
            ->recordActions([
                Action::make('preview')->label('Open staff preview')->icon('heroicon-o-eye')->url(fn (PublicationManifest $record) => route('questionnaires.sandbox.show', $record))->openUrlInNewTab()->visible(fn (PublicationManifest $record) => $record->channel === 'review_sandbox' && $record->retirement === null && auth()->user()?->can('questionnaires.preview.sandbox')),
                Action::make('retire')->label('Retire sandbox publication')->icon('heroicon-o-archive-box')->color('danger')->visible(fn (PublicationManifest $record) => $record->retirement === null && auth()->user()?->can('questionnaires.retire'))->schema([Textarea::make('reason')->label('Retirement reason')->helperText('Explain why this publication should no longer be available to its audience.')->required()])->modalHeading('Retire this sandbox publication?')->modalDescription('The immutable publication history will remain available, but new access or work using this publication will stop under the existing retirement rules.')->modalSubmitActionLabel('Retire publication')->modalContent(view('filament.partials.staff-action-recovery-notice'))->requiresConfirmation()->action(function (PublicationManifest $record, array $data) {
                    if (! StaffActionFeedback::run(fn () => app(QuestionnairePublicationService::class)->retire(auth()->user()->fresh(), $record, $data['reason']))) {
                        return;
                    }
                    $record->load('retirement');
                    Notification::make()->title('Sandbox publication retired')->body('Publication history is retained. Existing application history is not erased.')->success()->send();
                }),
            ])->emptyStateHeading('No publications match this view')->emptyStateDescription('Approved configurations can be published separately for staff preview or eligible synthetic applicants. Check the audience filter or return to Questionnaire approvals.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['retirement', 'module']);
    }

    public static function getPages(): array
    {
        return ['index' => ListPublicationManifests::route('/')];
    }
}
