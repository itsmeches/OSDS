<?php

namespace App\Filament\Resources\FormVersions;

use App\Filament\Forms\ConditionRuleField;
use App\Filament\Resources\FormVersions\Pages\CreateFormVersion;
use App\Filament\Resources\FormVersions\Pages\EditFormVersion;
use App\Filament\Resources\FormVersions\Pages\ListFormVersions;
use App\Models\FormVersion;
use App\Models\RequirementSetVersion;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormVersionResource extends Resource
{
    protected static ?string $model = FormVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('form_definition_id')->relationship('definition', 'name')->required()->disabledOn('edit'),
            TextInput::make('version_number')->numeric()->required()->minValue(1)->disabledOn('edit'),
            TextInput::make('state')->disabled()->dehydrated(false),
            Hidden::make('created_by')->default(fn () => auth()->id()),
            Repeater::make('sections')->relationship()->orderColumn('sort_order')->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? 'Section')->schema([
                TextInput::make('key')->required()->alphaDash(), TextInput::make('title')->required(), Textarea::make('description'),
                Repeater::make('questions')->relationship()->orderColumn('sort_order')->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? 'Question')->schema([
                    TextInput::make('key')->required()->alphaDash(), Select::make('type')->required()->options(array_combine(['short_text', 'long_text', 'telephone', 'email', 'date', 'integer', 'decimal', 'single_choice', 'multiple_choice', 'yes_no', 'declaration'], ['Short text', 'Long text', 'Telephone', 'Email', 'Date', 'Integer', 'Decimal', 'Single choice', 'Multiple choice', 'Yes/No', 'Declaration'])),
                    TextInput::make('label')->required(), Textarea::make('help_text'), Toggle::make('is_required'),
                    Textarea::make('validation_rules')->label('Validation JSON')->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)->dehydrateStateUsing(fn ($state) => blank($state) ? [] : json_decode($state, true, flags: JSON_THROW_ON_ERROR))->rule('json'),
                    ConditionRuleField::make(),
                    Repeater::make('options')->relationship()->orderColumn('sort_order')->reorderable()->schema([TextInput::make('key')->required()->alphaDash(), TextInput::make('label')->required()])->columns(2),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('definition.module.name')->badge(), TextColumn::make('definition.name')->searchable(), TextColumn::make('version_number')->label('Version'), TextColumn::make('state')->badge(), TextColumn::make('configuration_hash')->limit(12)->copyable()])->recordActions([
            EditAction::make()->visible(fn (FormVersion $record) => $record->state === 'draft'),
            Action::make('clone')->icon('heroicon-o-document-duplicate')->action(function (FormVersion $record) {
                app(QuestionnaireDraftService::class)->cloneForm(auth()->user(), $record);
                Notification::make()->title('Draft form version cloned')->success()->send();
            }),
            Action::make('submitReview')->label('Submit for review')->visible(fn (FormVersion $record) => $record->state === 'draft')->schema([Select::make('requirement_set_version_id')->label('Draft requirement set')->options(fn (FormVersion $record) => RequirementSetVersion::query()->where('module_id', $record->definition->module_id)->where('state', 'draft')->pluck('version_number', 'id'))->required()])->action(function (FormVersion $record, array $data) {
                app(QuestionnairePublicationService::class)->submitForReview(auth()->user(), $record, RequirementSetVersion::findOrFail($data['requirement_set_version_id']));
                Notification::make()->title('Configuration submitted for review')->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListFormVersions::route('/'), 'create' => CreateFormVersion::route('/create'), 'edit' => EditFormVersion::route('/{record}/edit')];
    }
}
