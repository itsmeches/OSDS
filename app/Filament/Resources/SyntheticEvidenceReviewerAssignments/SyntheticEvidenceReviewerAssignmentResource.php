<?php

namespace App\Filament\Resources\SyntheticEvidenceReviewerAssignments;

use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\CreateSyntheticEvidenceReviewerAssignment;
use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\ListSyntheticEvidenceReviewerAssignments;
use App\Models\SyntheticEvidenceReviewerAssignment;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SyntheticEvidenceReviewerAssignmentResource extends Resource
{
    protected static ?string $model = SyntheticEvidenceReviewerAssignment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'User and access management';

    protected static ?string $navigationLabel = 'Test evidence reviewers';

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
        return $s->components([Select::make('reviewer_id')->relationship('reviewer', 'name', modifyQueryUsing: fn ($q) => $q->where('is_synthetic', true))->required()->searchable(), Select::make('region_id')->relationship('region', 'name')->required(), Select::make('module_id')->relationship('module', 'name')->required(), DateTimePicker::make('starts_at')->default(now())->required(), DateTimePicker::make('ends_at')->after('starts_at'), Textarea::make('reason')->required()->maxLength(500)]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('reviewer.name')->label('Reviewer'), TextColumn::make('region.name'), TextColumn::make('module.name'), TextColumn::make('starts_at')->dateTime('M j, Y g:i A'), TextColumn::make('ends_at')->dateTime()->placeholder('No end date'), TextColumn::make('revoked_at')->dateTime()->placeholder('Active')])->recordActions([Action::make('revoke')->requiresConfirmation()->schema([Textarea::make('revocation_reason')->required()->maxLength(500)])->visible(fn ($record) => ! $record->revoked_at)->action(fn ($record, array $data) => $record->update(['revoked_at' => now(), 'revoked_by' => auth()->id(), 'revocation_reason' => $data['revocation_reason']]))]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSyntheticEvidenceReviewerAssignments::route('/'), 'create' => CreateSyntheticEvidenceReviewerAssignment::route('/create')];
    }
}
