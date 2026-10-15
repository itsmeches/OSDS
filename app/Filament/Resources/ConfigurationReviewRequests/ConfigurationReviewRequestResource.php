<?php

namespace App\Filament\Resources\ConfigurationReviewRequests;

use App\Filament\Resources\ConfigurationReviewRequests\Pages\ListConfigurationReviewRequests;
use App\Filament\Resources\ConfigurationReviewRequests\Pages\ViewConfigurationReviewRequest;
use App\Models\ConfigurationReviewRequest;
use App\Support\QuestionnairePresentation;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConfigurationReviewRequestResource extends Resource
{
    protected static ?string $model = ConfigurationReviewRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $navigationLabel = 'Questionnaire approvals';

    protected static ?string $modelLabel = 'questionnaire review';

    protected static ?string $pluralModelLabel = 'questionnaire approvals';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->can('questionnaires.review')
            ? 'Questionnaire approvals'
            : 'My review submissions';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()?->can('questionnaires.review')) {
            $query->where('submitted_by', auth()->id());
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('module.name')->label('Program')->searchable()->weight('semibold'),
                TextColumn::make('state')->label('Status')->badge()
                    ->formatStateUsing(fn (?string $state): string => QuestionnairePresentation::statusLabel($state))
                    ->color(fn (?string $state): string => QuestionnairePresentation::statusColor($state))
                    ->icon(fn (?string $state) => QuestionnairePresentation::statusIcon($state)),
                TextColumn::make('submittedBy.name')->label('Submitted by')->toggleable(),
                TextColumn::make('submitted_at')->label('Submitted')->dateTime('M j, Y g:i A')->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')->options([
                    'pending' => 'Pending review',
                    'approved' => 'Approved',
                    'rejected' => 'Changes requested',
                    'invalidated' => 'Review invalidated',
                ])->default('pending'),
            ])
            ->recordActions([
                ViewAction::make()->label('Open review'),
            ])
            ->emptyStateHeading('No questionnaire reviews found')
            ->emptyStateDescription('New submissions from OSDS will appear here. Change the status filter to view review history.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfigurationReviewRequests::route('/'),
            'view' => ViewConfigurationReviewRequest::route('/{record}'),
        ];
    }
}
