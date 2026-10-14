<?php

namespace App\Filament\Resources\ConfigurationReviewRequests;

use App\Filament\Resources\ConfigurationReviewRequests\Pages\ListConfigurationReviewRequests;
use App\Models\ConfigurationReviewRequest;
use App\Services\Questionnaires\QuestionnairePublicationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfigurationReviewRequestResource extends Resource
{
    protected static ?string $model = ConfigurationReviewRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('module.name')->badge(), TextColumn::make('formVersion.version_number')->label('Form'), TextColumn::make('requirementSetVersion.version_number')->label('Requirements'), TextColumn::make('combined_hash')->limit(12)->copyable(), TextColumn::make('state')->badge(), TextColumn::make('submitted_at')->dateTime()])->recordActions([
            Action::make('approve')->visible(fn (ConfigurationReviewRequest $record) => $record->state === 'pending' && auth()->user()?->can('questionnaires.review'))->schema([TextInput::make('reference')->required(), Textarea::make('comment')])->action(function (ConfigurationReviewRequest $record, array $data) {
                app(QuestionnairePublicationService::class)->review(auth()->user(), $record, true, $data['reference'], $data['comment'] ?? null);
                Notification::make()->title('Exact configuration approved')->success()->send();
            }),
            Action::make('reject')->color('danger')->visible(fn (ConfigurationReviewRequest $record) => $record->state === 'pending' && auth()->user()?->can('questionnaires.review'))->schema([TextInput::make('reference')->required(), Textarea::make('comment')->required()])->action(function (ConfigurationReviewRequest $record, array $data) {
                app(QuestionnairePublicationService::class)->review(auth()->user(), $record, false, $data['reference'], $data['comment']);
                Notification::make()->title('Configuration returned to draft')->warning()->send();
            }),
            Action::make('publish')->label('Publish to sandbox')->visible(fn (ConfigurationReviewRequest $record) => $record->state === 'approved' && auth()->user()?->can('questionnaires.publish.sandbox'))->schema([DateTimePicker::make('effective_from')->default(now())->required(), DateTimePicker::make('effective_until')->after('effective_from')])->action(function (ConfigurationReviewRequest $record, array $data) {
                $approval = $record->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                app(QuestionnairePublicationService::class)->publishSandbox(auth()->user(), $approval, $data['effective_from'], $data['effective_until'] ?? null);
                Notification::make()->title('Synthetic sandbox publication created')->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListConfigurationReviewRequests::route('/')];
    }
}
