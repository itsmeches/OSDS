<?php

namespace App\Filament\Resources\SyntheticApplicationReviewerAssignments;

use App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages\CreateSyntheticApplicationReviewerAssignment;
use App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages\ListSyntheticApplicationReviewerAssignments;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Services\Applications\ApplicationReviewerAssignmentService;
use App\Support\GrantPresentation;
use App\Support\StaffActionFeedback;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SyntheticApplicationReviewerAssignmentResource extends Resource
{
    protected static ?int $navigationSort = 50;

    protected static ?string $model = SyntheticApplicationReviewerAssignment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Institutions and Access';

    protected static ?string $navigationLabel = 'Application reviewer assignments';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('applications.reviewer.assignments.manage');
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('reviewer_id')->relationship('reviewer', 'name', modifyQueryUsing: fn ($query) => $query->where('is_synthetic', true))->required()->searchable()->extraAttributes(['class' => 'application-reviewer-selector']),
            Select::make('region_id')->relationship('region', 'name')->required(),
            Select::make('module_id')->relationship('module', 'name')->required(),
            DateTimePicker::make('starts_at')->default(now())->required(), DateTimePicker::make('ends_at')->after('starts_at'),
            Textarea::make('reason')->required()->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reviewer.name')->label('Reviewer')->searchable(), TextColumn::make('region.name')->label('Region')->searchable(), TextColumn::make('module.name')->label('Service')->searchable(),
            TextColumn::make('starts_at')->dateTime('M j, Y g:i A'), TextColumn::make('ends_at')->dateTime()->placeholder('No end date'), TextColumn::make('effective_state')->label('Access status')->badge()->getStateUsing(fn ($record): string => GrantPresentation::state($record))->color(fn (string $state): string => GrantPresentation::color($state)), TextColumn::make('revoked_at')->dateTime()->placeholder('Not revoked'),
        ])->recordActions([Action::make('revoke')->requiresConfirmation()->modalDescription(fn ($livewire) => $livewire->getErrorBag()->first('applicationReviewerRevocation') ?: 'Revoke this application-reviewer assignment? This removes its review authority and preserves its history.')->schema([Textarea::make('revocation_reason')->required()->maxLength(500)])->visible(fn ($record) => ! $record->revoked_at)->action(function ($record, array $data, $livewire): void {
            $livewire->resetErrorBag('applicationReviewerRevocation');
            try {
                StaffActionFeedback::run(fn () => app(ApplicationReviewerAssignmentService::class)->revoke(auth()->user(), $record, $data['revocation_reason']));
            } catch (Halt $exception) {
                $livewire->addError('applicationReviewerRevocation', 'The revocation could not be confirmed. Check the saved assignment before trying again.');
                throw $exception;
            }
        })]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSyntheticApplicationReviewerAssignments::route('/'), 'create' => CreateSyntheticApplicationReviewerAssignment::route('/create')];
    }
}
