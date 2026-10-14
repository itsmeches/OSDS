<?php

namespace App\Filament\Resources\PublicationManifests;

use App\Filament\Resources\PublicationManifests\Pages\ListPublicationManifests;
use App\Models\PublicationManifest;
use App\Services\Questionnaires\QuestionnairePublicationService;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('module.name')->badge(), TextColumn::make('channel')->badge(), TextColumn::make('formVersion.version_number')->label('Form'), TextColumn::make('requirementSetVersion.version_number')->label('Requirements'), TextColumn::make('combined_hash')->limit(12)->copyable(), TextColumn::make('effective_from')->dateTime(), TextColumn::make('retirement.retired_at')->label('Retired')->dateTime()])->recordActions([
            Action::make('preview')->url(fn (PublicationManifest $record) => route('questionnaires.sandbox.show', $record))->openUrlInNewTab()->visible(fn (PublicationManifest $record) => ! $record->retirement()->exists() && auth()->user()?->can('questionnaires.preview.sandbox')),
            Action::make('retire')->color('danger')->visible(fn (PublicationManifest $record) => ! $record->retirement()->exists() && auth()->user()?->can('questionnaires.retire'))->schema([Textarea::make('reason')->required()])->action(function (PublicationManifest $record, array $data) {
                app(QuestionnairePublicationService::class)->retire(auth()->user(), $record, $data['reason']);
                Notification::make()->title('Sandbox publication retired')->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPublicationManifests::route('/')];
    }
}
