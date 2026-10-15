<?php

namespace App\Filament\Resources\PublicationManifests;

use App\Filament\Resources\PublicationManifests\Pages\ListPublicationManifests;
use App\Models\PublicationManifest;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\QuestionnairePresentation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('module.name')->label('Program')->weight('semibold')->searchable(),
                TextColumn::make('publication_status')->label('Status')->badge()
                    ->getStateUsing(fn (PublicationManifest $record): string => $record->retirement()->exists() ? 'retired' : 'published')
                    ->formatStateUsing(fn (?string $state): string => QuestionnairePresentation::statusLabel($state))
                    ->color(fn (?string $state): string => QuestionnairePresentation::statusColor($state))
                    ->icon(fn (?string $state) => QuestionnairePresentation::statusIcon($state)),
                TextColumn::make('published_at')->label('Published')->dateTime('M j, Y g:i A')->sortable(),
                TextColumn::make('effective_until')->label('Available until')->dateTime('M j, Y g:i A')->placeholder('No end date')->toggleable(),
                TextColumn::make('combined_hash')->label('Configuration reference')->limit(12)->copyable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')->url(fn (PublicationManifest $record) => route('questionnaires.sandbox.show', $record))->openUrlInNewTab()->visible(fn (PublicationManifest $record) => ! $record->retirement()->exists() && auth()->user()?->can('questionnaires.preview.sandbox')),
                Action::make('retire')->label('Retire sandbox publication')->icon('heroicon-o-archive-box')->color('danger')->visible(fn (PublicationManifest $record) => ! $record->retirement()->exists() && auth()->user()?->can('questionnaires.retire'))->schema([Textarea::make('reason')->label('Retirement reason')->helperText('Explain why staff should no longer use this sandbox preview.')->required()])->modalHeading('Retire this sandbox publication?')->modalDescription('The immutable publication history will remain available, but staff will no longer be able to open this preview.')->modalSubmitActionLabel('Retire publication')->requiresConfirmation()->action(function (PublicationManifest $record, array $data) {
                    app(QuestionnairePublicationService::class)->retire(auth()->user(), $record, $data['reason']);
                    Notification::make()->title('Sandbox publication retired')->body('The publication remains in history and can no longer be previewed.')->success()->send();
                }),
            ])->emptyStateHeading('No sandbox publications yet')->emptyStateDescription('After an Admin approves a questionnaire, publish it to the staff-only review sandbox from Questionnaire approvals.');
    }

    public static function getPages(): array
    {
        return ['index' => ListPublicationManifests::route('/')];
    }
}
