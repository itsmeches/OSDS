<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\ListApplicantSandboxEligibilityGrants;
use App\Filament\Resources\AuditEvents\Pages\ListAuditEvents;
use App\Filament\Resources\ConfigurationReviewRequests\Pages\ListConfigurationReviewRequests;
use App\Filament\Resources\ConfigurationReviewRequests\Pages\ViewConfigurationReviewRequest;
use App\Filament\Resources\EvidenceProviderTypes\Pages\ListEvidenceProviderTypes;
use App\Filament\Resources\InstitutionRepresentatives\Pages\EditInstitutionRepresentative;
use App\Filament\Resources\InstitutionRepresentatives\Pages\ListInstitutionRepresentatives;
use App\Filament\Resources\Institutions\Pages\ListInstitutions;
use App\Filament\Resources\OsdsModules\Pages\ListOsdsModules;
use App\Filament\Resources\PublicationManifests\Pages\ListPublicationManifests;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Filament\Resources\SyntheticApplicationReviewerAssignments\Pages\ListSyntheticApplicationReviewerAssignments;
use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\ListSyntheticEvidenceReviewerAssignments;
use App\Filament\Resources\UserRegionAssignments\Pages\EditUserRegionAssignment;
use App\Filament\Resources\UserRegionAssignments\Pages\ListUserRegionAssignments;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Middleware\EnsureAccountIsActive;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('CHED OSDS Management Platform')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode()
            ->themeSwitcher()
            ->defaultThemeMode(ThemeMode::System)
            ->maxContentWidth(Width::SevenExtraLarge)
            ->sidebarWidth('17rem')
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->renderHook(TablesRenderHook::CONTENT_AFTER, fn () => view('filament.partials.staff-table-keyboard'), scopes: [
                ListUsers::class,
                ListRegions::class,
                ListInstitutions::class,
                ListInstitutionRepresentatives::class,
                ListUserRegionAssignments::class,
                ListAuditEvents::class,
                ListConfigurationReviewRequests::class,
                ListPublicationManifests::class,
                ListApplicantSandboxEligibilityGrants::class,
                ListSyntheticApplicationReviewerAssignments::class,
                ListSyntheticEvidenceReviewerAssignments::class,
                ListOsdsModules::class,
                ListEvidenceProviderTypes::class,
            ])
            ->renderHook(PanelsRenderHook::PAGE_START, fn () => view('filament.partials.staff-action-recovery'), scopes: [
                ViewConfigurationReviewRequest::class,
                ListSyntheticEvidenceReviewerAssignments::class,
                ListSyntheticApplicationReviewerAssignments::class,
                ListPublicationManifests::class,
                ListInstitutionRepresentatives::class,
                EditInstitutionRepresentative::class,
                EditUserRegionAssignment::class,
                CreateUser::class,
                EditUser::class,
            ])
            ->colors([
                'primary' => Color::hex('#245f9d'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                EnsureAccountIsActive::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
