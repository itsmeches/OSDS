<?php

namespace App\Filament\Resources\SyntheticEvidenceReviewerAssignments;

use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\CreateSyntheticEvidenceReviewerAssignment;
use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\ListSyntheticEvidenceReviewerAssignments;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Services\Evidence\EvidenceReviewerAssignmentService;
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

class SyntheticEvidenceReviewerAssignmentResource extends Resource
{
    protected static ?int $navigationSort = 60;

    protected static ?string $model = SyntheticEvidenceReviewerAssignment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Institutions and Access';

    protected static ?string $navigationLabel = 'Evidence reviewer assignments';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('evidence.reviewer.assignments.manage');
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([Select::make('reviewer_id')->relationship('reviewer', 'name', modifyQueryUsing: fn ($query) => $query->where('is_synthetic', true))->required()->searchable()->extraAttributes(['class' => 'evidence-reviewer-selector']), Select::make('region_id')->relationship('region', 'name')->required(), Select::make('module_id')->relationship('module', 'name')->required(), DateTimePicker::make('starts_at')->default(now())->required(), DateTimePicker::make('ends_at')->after('starts_at'), Textarea::make('reason')->required()->maxLength(500)]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('reviewer.name')->label('Reviewer')->searchable(), TextColumn::make('region.name')->label('Region')->searchable(), TextColumn::make('module.name')->label('Service')->searchable(), TextColumn::make('starts_at')->dateTime('M j, Y g:i A'), TextColumn::make('ends_at')->dateTime()->placeholder('No end date'), TextColumn::make('effective_state')->label('Access status')->badge()->getStateUsing(fn ($record): string => GrantPresentation::state($record))->color(fn (string $state): string => GrantPresentation::color($state)), TextColumn::make('revoked_at')->dateTime()->placeholder('Not revoked')])->recordActions([Action::make('revoke')->requiresConfirmation()->modalDescription(fn ($livewire) => $livewire->getErrorBag()->first('reviewerRevocation') ?: 'Revoke this reviewer assignment? This removes its review authority and preserves its history.')->schema([Textarea::make('revocation_reason')->required()->maxLength(500)])->visible(fn ($record) => ! $record->revoked_at)->action(function ($record, array $data, $livewire): void {
            $livewire->resetErrorBag('reviewerRevocation');
            try {
                StaffActionFeedback::run(fn () => app(EvidenceReviewerAssignmentService::class)->revoke(auth()->user(), $record, $data['revocation_reason']));
            } catch (Halt $exception) {
                $livewire->addError('reviewerRevocation', 'The revocation could not be confirmed. Check the saved assignment before trying again.');
                throw $exception;
            }
        })]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSyntheticEvidenceReviewerAssignments::route('/'), 'create' => CreateSyntheticEvidenceReviewerAssignment::route('/create')];
    }
}
