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
        return 'Your work';
    }

    protected function getDescription(): ?string
    {
        return 'Open an available task to continue.';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can(FoundationPermission::ManageQuestionnaireDrafts->value)
            || $user?->can(FoundationPermission::SubmitQuestionnaireReview->value)) {
            $stats[] = Stat::make('Questionnaire drafts', FormVersion::query()->where('state', 'draft')->count())
                ->description('Manage questions and document requirements')
                ->descriptionIcon(Heroicon::OutlinedPencilSquare)
                ->color('primary')
                ->url(Questionnaires::getUrl());
        }

        if ($user?->can(FoundationPermission::ReviewQuestionnaires->value)) {
            $stats[] = Stat::make('Pending approvals', ConfigurationReviewRequest::query()->where('state', 'pending')->count())
                ->description('Review exact submitted configurations')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->color('warning')
                ->url(ConfigurationReviewRequestResource::getUrl());
        }

        if ($user?->can(FoundationPermission::ViewQuestionnairePublications->value)) {
            $stats[] = Stat::make('Sandbox publications', PublicationManifest::query()->whereDoesntHave('retirement')->count())
                ->description('Preview or review publication history')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success')
                ->url(PublicationManifestResource::getUrl());
        }

        return $stats;
    }
}
