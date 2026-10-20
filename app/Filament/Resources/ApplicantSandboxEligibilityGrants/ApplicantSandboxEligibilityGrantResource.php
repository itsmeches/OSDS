<?php

namespace App\Filament\Resources\ApplicantSandboxEligibilityGrants;

use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\CreateApplicantSandboxEligibilityGrant;
use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\ListApplicantSandboxEligibilityGrants;
use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Institution;
use App\Models\OsdsModule;
use App\Support\GrantPresentation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicantSandboxEligibilityGrantResource extends Resource
{
    protected static ?string $model = ApplicantSandboxEligibilityGrant::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'User and access management';

    protected static ?string $navigationLabel = 'Applicant sandbox eligibility';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('applicant.sandbox.publish') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('institution_id')
                ->label('Synthetic organization')
                ->options(fn () => Institution::query()->where('is_synthetic', true)->orderBy('name')->pluck('name', 'id'))
                ->helperText('Only synthetic organizations in the isolated applicant sandbox are eligible.')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('module_id')
                ->label('Questionnaire service')
                ->options(fn () => OsdsModule::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            DateTimePicker::make('starts_at')->label('Effective from')->default(now())->required(),
            DateTimePicker::make('ends_at')->label('Effective until')->helperText('Optional. Leave blank when no test end date is needed.')->after('starts_at'),
            Textarea::make('reason')->helperText('Explain why this organization may test this questionnaire service.')->required()->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('institution.name')->label('Organization')->searchable(), TextColumn::make('module.name')->label('Service')->searchable(), TextColumn::make('starts_at')->dateTime('M j, Y g:i A'), TextColumn::make('ends_at')->dateTime('M j, Y g:i A')->placeholder('No end date'), TextColumn::make('effective_state')->label('Access status')->badge()->getStateUsing(fn ($record): string => GrantPresentation::state($record))->color(fn (string $state): string => GrantPresentation::color($state)), TextColumn::make('revoked_at')->dateTime()->placeholder('Not revoked')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListApplicantSandboxEligibilityGrants::route('/'), 'create' => CreateApplicantSandboxEligibilityGrant::route('/create')];
    }
}
