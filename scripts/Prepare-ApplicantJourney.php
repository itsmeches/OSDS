<?php

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\EvidenceReference;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// CLI fixture control: every connection is pinned to the isolated browser database.
require __DIR__.'/../vendor/autoload.php';
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Browser fixtures require uncached configuration.');
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test') {
    throw new RuntimeException('Refusing to modify a non-browser database.');
}
$file = storage_path('app/private/applicant-journey.json');
$action = $argv[1] ?? 'prepare';
$school = User::where('email', 'school@example.invalid')->firstOrFail();
$admin = User::where('email', 'admin@example.invalid')->firstOrFail();
$reviewer = User::where('email', 'chedro@example.invalid')->firstOrFail();
if (in_array($action, ['prepare', 'prepare-matrix'], true)) {
    $osds = User::where('email', 'osds@example.invalid')->firstOrFail();
    $module = OsdsModule::where('code', 'registration')->firstOrFail();
    $region = Institution::where('official_id', 'SYN-001')->firstOrFail()->primary_region_id;
    $definition = FormDefinition::create(['module_id' => $module->id, 'key' => 'journey_'.Str::random(12), 'name' => 'Applicant journey test', 'owner_id' => $osds->id]);
    $form = FormVersion::create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $osds->id]);
    $section = FormSection::create(['form_version_id' => $form->id, 'key' => 'organization', 'title' => 'Organization details', 'sort_order' => 1]);
    $questionIds = [];
    foreach ([['name', 'short_text', 'Organization name', null], ['has_activity', 'yes_no', 'Do you have an activity to describe?', null], ['details', 'long_text', 'Activity details', ['question' => 'has_activity', 'operator' => 'equals', 'value' => 'yes']]] as $index => [$key, $type, $label, $condition]) {
        $questionIds[$key] = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => $key, 'type' => $type, 'label' => $label, 'is_required' => true, 'sort_order' => $index + 1, 'validation_rules' => [], 'condition_rules' => $condition])->id;
    }
    if ($action === 'prepare-matrix') {
        foreach ([['has_team', 'yes_no', 'Do you have a team?', null], ['team_details', 'short_text', 'Team details', ['question' => 'has_team', 'operator' => 'equals', 'value' => 'yes']], ['level', 'single_choice', 'School level', null], ['programs', 'multiple_choice', 'Programs', null]] as $index => [$key, $type, $label, $condition]) {
            $question = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => $key, 'type' => $type, 'label' => $label, 'is_required' => true, 'sort_order' => $index + 5, 'validation_rules' => [], 'condition_rules' => $condition]);
            $questionIds[$key] = $question->id;
            if (in_array($type, ['single_choice', 'multiple_choice'], true)) {
                foreach (['primary' => 'Primary', 'secondary' => 'Secondary'] as $option => $label) {
                    $question->options()->create(['key' => $option, 'label' => $label, 'sort_order' => $option === 'primary' ? 1 : 2]);
                }
            }
        }
    }
    $section = FormSection::create(['form_version_id' => $form->id, 'key' => 'contact', 'title' => 'Contact details', 'sort_order' => 2]);
    $questionIds['email'] = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'email', 'type' => 'email', 'label' => 'Contact email', 'is_required' => true, 'sort_order' => 4, 'validation_rules' => []])->id;
    $requirements = RequirementSetVersion::create(['module_id' => $module->id, 'version_number' => ((int) RequirementSetVersion::where('module_id', $module->id)->max('version_number')) + 1, 'state' => 'draft', 'created_by' => $osds->id]);
    $requirement = DocumentaryRequirement::create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Undertaking', 'instructions' => 'Add an individual Google document for this synthetic test.', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
    $requirement->providers()->attach(EvidenceProviderType::where('code', 'google_docs')->value('id'));
    $publisher = app(QuestionnairePublicationService::class);
    $approval = $publisher->review($admin, $publisher->submitForReview($osds, $form, $requirements), true, 'SYNTHETIC-JOURNEY');
    foreach (PublicationManifest::where('module_id', $module->id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->get() as $oldManifest) {
        $publisher->retire($admin, $oldManifest, 'Superseded for isolated Batch C browser verification');
    }
    $manifest = $publisher->publishApplicantSandbox($admin, $approval);
    $organization = Institution::create(['name' => 'Synthetic journey school', 'primary_region_id' => $region, 'is_synthetic' => true, 'organization_type' => 'hei']);
    InstitutionRepresentative::create(['institution_id' => $organization->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
    ApplicantSandboxEligibilityGrant::create(['institution_id' => $organization->id, 'module_id' => $module->id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated browser journey']);
    $fixture = ['institution' => $organization->id, 'manifest' => $manifest->id, 'questions' => $questionIds, 'requirement' => $requirement->id];
    file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR));
} else {
    $fixture = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $application = Application::where('institution_id', $fixture['institution'])->firstOrFail();
    $draft = $application->currentWorkingRevision?->draft ?? ApplicationDraft::where('application_id', $application->id)->latest('id')->firstOrFail();
    if ($action === 'correct') {
        $lifecycle = app(ApplicationLifecycleService::class);
        $lifecycle->beginScreening($reviewer, $application);
        $lifecycle->requestCorrection($reviewer, $application->fresh(), 'Please update the organization name.', [['target_kind' => 'question', 'form_question_id' => $fixture['questions']['name'], 'issue_category' => 'clarification', 'instruction' => 'Change the organization name to Synthetic corrected school.']]);
    } elseif ($action === 'retire') {
        $organization = Institution::create(['name' => 'Synthetic retired draft school', 'primary_region_id' => $application->institution->primary_region_id, 'is_synthetic' => true, 'organization_type' => 'hei']);
        InstitutionRepresentative::create(['institution_id' => $organization->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::create(['institution_id' => $organization->id, 'module_id' => $application->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated retired draft browser test']);
        $retiredDraft = app(ApplicantDraftService::class)->start($school, $organization, $application->manifest);
        app(ApplicantDraftService::class)->save($school, $retiredDraft, ['name' => 'Preserved retired answer'], 1, (string) Str::uuid());
        $fixture['retiredPath'] = '/applicant/drafts/'.$retiredDraft->public_id;
        app(QuestionnairePublicationService::class)->retire($admin, $application->manifest, 'Isolated retired-manifest browser test');
    } elseif (in_array($action, ['expire', 'revoke', 'restore'], true)) {
        // A date-boundary simulation within this isolated fixture, without touching other grants.
        DB::table('institution_representatives')->where('institution_id', $fixture['institution'])->where('user_id', $school->id)->update(match ($action) {
            'expire' => ['ends_at' => now()->subMinute()], 'revoke' => ['revoked_at' => now()], default => ['ends_at' => null, 'revoked_at' => null]
        });
    } elseif (! in_array($action, ['inspect', 'matrix-inspect'], true)) {
        throw new RuntimeException('Unsupported browser fixture action.');
    }
    $application->refresh();
    $fixture['applicationPath'] = '/applicant/applications/'.$application->public_id;
    $fixture['draftPath'] = '/applicant/drafts/'.($application->currentWorkingRevision?->draft ?? $draft)->public_id;
    $fixture['state'] = $application->state;
    $fixture['submissionCount'] = $application->submissions()->count();
    $fixture['draftVersion'] = $draft->fresh()->lock_version;
    $fixture['activityIsYes'] = $draft->answers()->where('form_question_id', $fixture['questions']['has_activity'])->value('value') === 'yes';
    if ($action === 'matrix-inspect') {
        $fixture['answerValues'] = $draft->answers()->get()->mapWithKeys(fn ($answer) => [$answer->form_question_id => $answer->value])->all();
        $fixture['missingAnswers'] = app(ApplicantDraftService::class)->missing($draft);
    }
    $fixture['evidenceCount'] = EvidenceReference::where('application_draft_id', $draft->id)->count();
    $fixture['originalAnswer'] = $application->submissions()->orderBy('submission_sequence')->first()?->answers()->where('question_key', 'name')->value('value');
    file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR));
}
echo "Isolated applicant fixture action completed: {$action}.\n";
