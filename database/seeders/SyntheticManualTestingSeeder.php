<?php

namespace Database\Seeders;

use App\Models\ConfigurationReviewRequest;
use App\Models\FormDefinition;
use App\Models\PublicationManifest;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Illuminate\Database\Seeder;
use RuntimeException;

class SyntheticManualTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['sandbox', 'testing']) || ! app(ApplicantSandboxGuard::class)->allowed()) {
            throw new RuntimeException('Manual-testing fixtures require an isolated sandbox or testing database.');
        }

        $osds = User::role(AccountRole::Osds->value)->where('is_synthetic', true)->firstOrFail();
        $admin = User::role(AccountRole::Admin->value)->where('is_synthetic', true)->firstOrFail();
        $publication = app(QuestionnairePublicationService::class);

        FormDefinition::query()
            ->where('key', 'initial_application')
            ->with(['module', 'versions' => fn ($query) => $query->orderBy('version_number')])
            ->orderBy('module_id')
            ->get()
            ->each(function (FormDefinition $definition) use ($osds, $admin, $publication): void {
                $module = $definition->module;
                if (PublicationManifest::query()->where('module_id', $module->id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->exists()) {
                    return;
                }

                $form = $definition->versions->firstWhere('state', 'draft');
                $requirements = RequirementSetVersion::query()->where('module_id', $module->id)->where('state', 'draft')->orderBy('version_number')->first();

                if ($form && $requirements) {
                    $review = $publication->submitForReview($osds, $form, $requirements);
                } else {
                    $review = ConfigurationReviewRequest::query()
                        ->whereHas('formVersion', fn ($query) => $query->where('form_definition_id', $definition->id))
                        ->whereIn('state', ['pending', 'approved'])
                        ->latest('id')
                        ->first();
                    if (! $review) {
                        throw new RuntimeException("No compatible synthetic drafts or active review are available for {$module->name}.");
                    }
                }
                if ($review->state === 'pending') {
                    $publication->review($admin, $review, true, 'SYNTHETIC-MANUAL-READINESS', 'Approved automatically by the isolated manual-testing fixture lifecycle.');
                    $review->refresh();
                }

                $approval = $review->approvals()->where('outcome', 'approved')->latest('id')->firstOrFail();
                $publication->publishApplicantSandbox($admin, $approval);
            });
    }
}
