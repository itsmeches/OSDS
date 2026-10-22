<?php

namespace Tests\Support;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\Region;
use App\Models\RequirementSetVersion;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\FoundationPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class B1Scenario
{
    public static function create(string $moduleCode = 'registration', bool $includeAddress = false): array
    {
        if (! in_array(DB::connection()->getDatabaseName(), ['osds_test', 'osds_ui_browser_test'], true)) {
            throw new \RuntimeException('B1 fixtures require an explicitly approved disposable database.');
        }
        auth()->logout();
        foreach (FoundationPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }
        $user = function (string $label, array $permissions): User {
            $actor = User::factory()->create(['name' => 'B1 synthetic '.$label, 'email' => 'b1-'.Str::lower(Str::random(10)).'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
            $actor->givePermissionTo($permissions);

            return $actor;
        };
        $author = $user('author', []);
        // Use the existing reviewed publication contract and its established bundles.
        $author->assignRole('OSDS');
        $publisher = $user('publisher', ['platform.admin.access']);
        $publisher->assignRole('Admin');
        $module = OsdsModule::where('code', $moduleCode)->firstOrFail();
        $publication = app(QuestionnairePublicationService::class);
        foreach (PublicationManifest::where('module_id', $module->id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->get() as $old) {
            $publication->retire($publisher, $old, 'Disposable B1 scenario preparation');
        }
        $definition = FormDefinition::create(['module_id' => $module->id, 'key' => 'b1_'.Str::random(8), 'name' => 'B1 synthetic '.$moduleCode, 'owner_id' => $author->id]);
        $form = FormVersion::create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $author->id]);
        $section = FormSection::create(['form_version_id' => $form->id, 'key' => 'identity', 'title' => 'Identity', 'sort_order' => 1]);
        $question = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'name', 'type' => 'short_text', 'label' => 'Synthetic organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
        $addressQuestion = $includeAddress ? FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'address', 'type' => 'short_text', 'label' => 'Synthetic approved address', 'is_required' => true, 'sort_order' => 2, 'validation_rules' => []]) : null;
        $unansweredQuestion = $includeAddress ? FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'optional_postal_note', 'type' => 'short_text', 'label' => 'Optional postal note', 'is_required' => false, 'sort_order' => 3, 'validation_rules' => []]) : null;
        $requirements = RequirementSetVersion::create(['module_id' => $module->id, 'version_number' => (int) RequirementSetVersion::where('module_id', $module->id)->max('version_number') + 1, 'state' => 'draft', 'created_by' => $author->id]);
        $requirement = DocumentaryRequirement::create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Synthetic undertaking', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
        $requirement->providers()->attach(EvidenceProviderType::where('code', 'google_drive_file')->value('id'));
        $manifest = $publication->publishApplicantSandbox($publisher, $publication->review($publisher, $publication->submitForReview($author, $form, $requirements), true, 'B1 synthetic configuration'));
        $region = Region::create(['code' => 'B1-'.Str::random(8), 'name' => 'B1 synthetic region']);
        $institution = Institution::create(['name' => 'B1 synthetic '.$moduleCode.' '.Str::random(6), 'primary_region_id' => $region->id, 'organization_type' => 'athletic_association', 'is_synthetic' => true]);
        $applicant = $user('applicant', ['applicant.portal.access', 'applications.draft.create', 'applications.draft.view', 'applications.draft.update', 'applications.submit', 'applications.resubmit', 'applications.track.own', 'applications.corrections.view.own', 'evidence.references.manage', 'evidence.references.open.own']);
        InstitutionRepresentative::create(['user_id' => $applicant->id, 'institution_id' => $institution->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $publisher->id]);
        ApplicantSandboxEligibilityGrant::create(['institution_id' => $institution->id, 'module_id' => $module->id, 'granted_by' => $publisher->id, 'starts_at' => now()->subDay(), 'reason' => 'B1 disposable fixture']);
        $staff = [];
        foreach (['evaluator' => 'applications.evaluation.record', 'chief' => 'applications.recommendations.record', 'director' => 'applications.decisions.record', 'contender' => 'applications.decisions.record'] as $role => $permission) {
            $staff[$role] = $user($role, ['platform.admin.access', 'applications.intake.view', 'applications.intake.answers.view', 'applications.intake.evidence.open', 'applications.screening.start', 'applications.corrections.request', 'applications.screening.history.view', $permission]);
            UserRegionAssignment::create(['user_id' => $staff[$role]->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
            SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $staff[$role]->id, 'region_id' => $region->id, 'module_id' => $module->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'granted_by' => $publisher->id, 'reason' => 'Disposable B1 exact-pair authority']);
        }
        $drafts = app(ApplicantDraftService::class);
        $draft = $drafts->start($applicant, $institution, $manifest);
        $drafts->save($applicant, $draft, ['name' => 'B1 synthetic organization'] + ($includeAddress ? ['address' => '123 Fictional Demo Road'] : []), $draft->lock_version, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($applicant, $draft->fresh(), $requirement, 'https://drive.google.com/file/d/B1SyntheticEvidence12345/view', $draft->fresh()->lock_version, (string) Str::uuid());
        $case = Application::findOrFail($draft->application_id);
        $lifecycle = app(ApplicationLifecycleService::class);
        $submission = $lifecycle->submit($applicant, $case, $case->lock_version, $draft->fresh()->lock_version, (string) Str::uuid());
        $lifecycle->beginScreening($staff['evaluator'], $case);
        $case = $case->fresh();

        return compact('applicant', 'case', 'submission', 'region', 'institution', 'module', 'manifest', 'question', 'addressQuestion', 'unansweredQuestion', 'requirement', 'staff', 'publisher', 'author');
    }
}
