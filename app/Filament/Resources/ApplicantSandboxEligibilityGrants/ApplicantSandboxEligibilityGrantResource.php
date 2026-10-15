<?php

namespace App\Filament\Resources\ApplicantSandboxEligibilityGrants;

use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\CreateApplicantSandboxEligibilityGrant;
use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\ListApplicantSandboxEligibilityGrants;
use App\Models\ApplicantSandboxEligibilityGrant;
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
        return $schema->components([Select::make('institution_id')->relationship('institution', 'name', modifyQueryUsing: fn ($q) => $q->where('is_synthetic', true))->required(), Select::make('module_id')->relationship('module', 'name')->required(), DateTimePicker::make('starts_at')->default(now())->required(), DateTimePicker::make('ends_at')->after('starts_at'), Textarea::make('reason')->required()->maxLength(500)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('institution.name')->label('Organization')->searchable(), TextColumn::make('module.name'), TextColumn::make('starts_at')->dateTime('M j, Y g:i A'), TextColumn::make('ends_at')->dateTime('M j, Y g:i A')->placeholder('No end date'), TextColumn::make('revoked_at')->dateTime()->placeholder('Active')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListApplicantSandboxEligibilityGrants::route('/'), 'create' => CreateApplicantSandboxEligibilityGrant::route('/create')];
    }
}
