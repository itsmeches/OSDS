<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Questionnaires;
use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\PublicationManifests\PublicationManifestResource;
use App\Models\ConfigurationReviewRequest;
use App\Models\FormVersion;
use App\Models\PublicationManifest;
use App\Support\FoundationPermission;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkflowOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isActive() && (
            $user->can(FoundationPermission::ViewQuestionnaires->value)
            || $user->can(FoundationPermission::ReviewQuestionnaires->value)
            || $user->can(FoundationPermission::ViewQuestionnairePublications->value)
        );
    }

    protected function getHeading(): ?string
    {
        return 'Questionnaire configuration overview';
    }

    protected function getDescription(): ?string
    {
        $user = auth()->user();
        $explanation = 'These configuration-record counts are not your personal application workload.';

        if (! $user?->can(FoundationPermission::ManageQuestionnaireDrafts->value)
            && ! $user?->can(FoundationPermission::SubmitQuestionnaireReview->value)
            && ! $user?->can(FoundationPermission::ReviewQuestionnaires->value)
            && ! $user?->can(FoundationPermission::ViewQuestionnairePublications->value)) {
            return 'No configuration statistics are visible for your current access. '.$explanation;
        }

        return $explanation;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can(FoundationPermission::ManageQuestionnaireDrafts->value)
            || $user?->can(FoundationPermission::SubmitQuestionnaireReview->value)) {
            $stats[] = Stat::make('Questionnaire drafts', FormVersion::query()->where('state', 'draft')->count())
                ->description('Draft questionnaire versions; not applicant drafts.')
                ->descriptionIcon(Heroicon::OutlinedPencilSquare)
                ->color('primary')
                ->url(Questionnaires::getUrl());
        }

        if ($user?->can(FoundationPermission::ReviewQuestionnaires->value)) {
            $stats[] = Stat::make('Pending questionnaire reviews', ConfigurationReviewRequest::query()->where('state', 'pending')->count())
                ->description('Configuration reviews awaiting review; not application decisions.')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->color('warning')
                ->url(ConfigurationReviewRequestResource::getUrl());
        }

        if ($user?->can(FoundationPermission::ViewQuestionnairePublications->value)) {
            $stats[] = Stat::make('Questionnaire publications (not retired)', PublicationManifest::query()->whereDoesntHave('retirement')->count())
                ->description('May include scheduled or ended availability; not a count of open services.')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success')
                ->url(PublicationManifestResource::getUrl());
        }

        return $stats;
    }
}
