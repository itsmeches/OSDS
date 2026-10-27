<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CaseDemonstrationDocuments;
use App\Filament\Pages\DownloadableForms;
use App\Filament\Pages\IssuanceDefinitions;
use App\Filament\Pages\Questionnaires;
use App\Filament\Pages\SyntheticApplicationIntake;
use App\Filament\Pages\SyntheticEvidenceReviews;
use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use App\Filament\Resources\Institutions\InstitutionResource;
use App\Filament\Resources\PublicationManifests\PublicationManifestResource;
use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Widgets\Widget;

class TaskOrientation extends Widget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.task-orientation';

    public static function canView(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->can('platform.admin.access');
    }

    /** Static destinations only. Destination policies still govern each page. */
    public static function catalogue(): array
    {
        return [
            'Applications and Reviews' => [
                [SyntheticApplicationIntake::class, 'Applications and decisions', 'View assigned applications. Case actions depend on your current assignment and the submitted revision.', 'heroicon-o-inbox-arrow-down'],
                [SyntheticEvidenceReviews::class, 'Assigned evidence test reviews', 'Open assigned evidence test reviews. Findings do not constitute a final application decision.', 'heroicon-o-document-magnifying-glass'],
            ],
            'Forms and Demonstration Documents' => [
                [Questionnaires::class, 'Application questionnaires', 'Manage application questions and documentary requirements available to your account.', 'heroicon-o-clipboard-document-list'],
                [ConfigurationReviewRequestResource::class, 'Questionnaire configuration reviews', 'Open configuration reviews and your review submissions as permitted. These are not application decisions.', 'heroicon-o-check-badge'],
                [PublicationManifestResource::class, 'Published questionnaires', 'Inspect publication records and their audience and availability.', 'heroicon-o-eye'],
                [DownloadableForms::class, 'Download blank forms', 'Read the current blank-form catalogue. Management actions require their existing permission.', 'heroicon-o-arrow-down-tray'],
                [IssuanceDefinitions::class, 'Issuance request definitions', 'Manage versioned case-linked request forms according to your existing permissions.', 'heroicon-o-clipboard-document-list'],
                [CaseDemonstrationDocuments::class, 'Case-linked demonstration PDFs', 'Open permitted demonstration document tasks. Generation and download require their existing checks.', 'heroicon-o-document-text'],
            ],
            'Administration' => [
                [UserResource::class, 'Users', 'Open account records within your authorized access.', 'heroicon-o-users'],
                [InstitutionResource::class, 'Institutions', 'Open authorized institution records.', 'heroicon-o-building-library'],
                [InstitutionRepresentativeResource::class, 'Institution representatives', 'Open authorized institution representation records.', 'heroicon-o-identification'],
                [UserRegionAssignmentResource::class, 'Regional assignments', 'Open regional authority records available to your account.', 'heroicon-o-map-pin'],
            ],
        ];
    }

    public static function responsibilityDescriptions(): array
    {
        return [
            'applications.evaluation.record' => 'Review the current submitted copy and record a technical evaluation. Request corrections when your account is authorized.',
            'applications.recommendations.record' => 'Review the evaluation, recommend approval or return the case for reevaluation when the current stage permits it.',
            'applications.decisions.record' => 'Review the recommendation and record a final demonstration approval or rejection when permitted.',
        ];
    }

    public function taskGroups(): array
    {
        if (! static::canView()) {
            return [];
        }

        $groups = [];
        $seen = [];
        foreach (static::catalogue() as $heading => $destinations) {
            foreach ($destinations as [$destination, $label, $description, $icon]) {
                if (isset($seen[$destination]) || ! $destination::canAccess()) {
                    continue;
                }
                $seen[$destination] = true;
                $responsibilities = [];
                if ($destination === SyntheticApplicationIntake::class) {
                    foreach (static::responsibilityDescriptions() as $permission => $text) {
                        if (auth()->user()->can($permission)) {
                            $responsibilities[] = $text;
                        }
                    }
                }
                if ($destination === ConfigurationReviewRequestResource::class) {
                    $label = $destination::getNavigationLabel();
                }
                $groups[$heading][] = compact('label', 'description', 'icon', 'responsibilities') + ['url' => $destination::getUrl()];
            }
        }

        return $groups;
    }

    protected function getViewData(): array
    {
        return ['groups' => $this->taskGroups()];
    }
}
