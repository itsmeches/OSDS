<?php

namespace App\Filament\Resources\RequirementSetVersions;

use App\Filament\Forms\ConditionRuleField;
use App\Filament\Resources\RequirementSetVersions\Pages\CreateRequirementSetVersion;
use App\Filament\Resources\RequirementSetVersions\Pages\EditRequirementSetVersion;
use App\Filament\Resources\RequirementSetVersions\Pages\ListRequirementSetVersions;
use App\Models\RequirementSetVersion;
use App\Services\Questionnaires\QuestionnaireDraftService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
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

class RequirementSetVersionResource extends Resource
{
    protected static ?string $model = RequirementSetVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Questionnaire configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('module_id')->relationship('module', 'name')->required()->disabledOn('edit'),
            TextInput::make('version_number')->numeric()->required()->minValue(1)->disabledOn('edit'),
            TextInput::make('state')->disabled()->dehydrated(false), Hidden::make('created_by')->default(fn () => auth()->id()),
            Repeater::make('requirements')->relationship()->orderColumn('sort_order')->reorderable()->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? 'Requirement')->schema([
                TextInput::make('key')->required()->alphaDash(), TextInput::make('label')->required(), Textarea::make('instructions'), Toggle::make('is_required')->default(true),
                TextInput::make('minimum_evidence')->numeric()->required()->minValue(0)->default(1), TextInput::make('maximum_evidence')->numeric()->required()->minValue(1)->default(1)->gte('minimum_evidence'),
                CheckboxList::make('providers')->relationship('providers', 'name')->required()->columns(2),
                ConditionRuleField::make(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('module.name')->badge(), TextColumn::make('version_number')->label('Version'), TextColumn::make('state')->badge(), TextColumn::make('requirements_count')->counts('requirements')->label('Requirements'), TextColumn::make('configuration_hash')->limit(12)->copyable()])->recordActions([
            EditAction::make()->visible(fn (RequirementSetVersion $record) => $record->state === 'draft'),
            Action::make('clone')->icon('heroicon-o-document-duplicate')->action(function (RequirementSetVersion $record) {
                app(QuestionnaireDraftService::class)->cloneRequirements(auth()->user(), $record);
                Notification::make()->title('Draft requirement set cloned')->success()->send();
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRequirementSetVersions::route('/'), 'create' => CreateRequirementSetVersion::route('/create'), 'edit' => EditRequirementSetVersion::route('/{record}/edit')];
    }
}
