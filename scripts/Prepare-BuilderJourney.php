<?php

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftAnswer;
use App\Models\ApplicationSubmission;
use App\Models\ConfigurationReviewRequest;
use App\Models\EvidenceProviderType;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Browser fixture requires uncached configuration.');
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test') {
    throw new RuntimeException('Refusing non-browser database.');
}
$file = storage_path('app/private/builder-journey.json');
$action = $argv[1] ?? 'prepare';
$osds = User::where('email', 'osds@example.invalid')->firstOrFail();
$admin = User::where('email', 'admin@example.invalid')->firstOrFail();
$school = User::where('email', 'school@example.invalid')->firstOrFail();
if ($action === 'inspect-selected') {
    $form = FormVersion::findOrFail((int) ($argv[2] ?? 0));
    $requirements = RequirementSetVersion::findOrFail((int) ($argv[3] ?? 0));
    if ($form->created_by !== $osds->id || $form->definition->module_id !== $requirements->module_id || $form->definition->name !== 'Browser authoring acceptance') {
        throw new RuntimeException('Refusing an unrelated fixture pair.');
    }
    $fixture = ['form' => $form->id, 'requirements' => $requirements->id, 'builder' => '/admin/form-builder/'.$form->id.'/'.$requirements->id];
} elseif (in_array($action, ['prepare', 'prepare-matrix'], true)) {
    $module = OsdsModule::where('code', 'registration')->firstOrFail();
    [$form,$requirements] = app(QuestionnaireDraftService::class)->createPair($osds, $module, 'Synthetic condition preservation');
    $leaf = fn ($value) => ['question' => 'source', 'operator' => 'equals', 'value' => $value];
    $rules = ['any' => [$leaf('Alpha'), ['all' => [$leaf('Beta'), ['question' => 'source', 'operator' => 'not_equals', 'value' => 'Blocked']]]]];
    $question = fn ($key, $label, $condition) => ['key' => $key, 'label' => $label, 'type' => 'short_text', 'help_text' => '', 'is_required' => false, 'validation_rules' => [], 'options' => [], 'condition_rules' => $condition];
    $provider = EvidenceProviderType::where('code', 'google_docs')->value('id');
    app(QuestionnaireAuthoringService::class)->save($osds, $form, $requirements, [
        'title' => 'Synthetic condition preservation',
        'sections' => [['key' => 'main', 'title' => 'Applicant details', 'description' => '', 'questions' => [
            $question('source', 'Organization category', null), $question('dependent', 'Conditional information', $rules), $question('simple', 'Simple information', $leaf('Alpha')),
        ]]],
        'requirements' => [['key' => 'support', 'label' => 'Conditional supporting document', 'instructions' => 'Synthetic test only', 'is_required' => false, 'minimum_evidence' => 0, 'maximum_evidence' => 1, 'provider_ids' => [$provider], 'condition_rules' => $rules]],
    ], 0, 0);
    if ($action === 'prepare-matrix') {
        $section = $form->sections()->firstOrFail();
        foreach (['choice_a', 'choice_b'] as $key) {
            $choice = $section->questions()->create(['form_version_id' => $form->id, 'key' => $key, 'label' => $key === 'choice_a' ? 'First choice question' : 'Second choice question', 'type' => 'single_choice', 'is_required' => false, 'sort_order' => $key === 'choice_a' ? 4 : 5, 'validation_rules' => []]);
            foreach (['one', 'two'] as $index => $option) {
                $choice->options()->create(['key' => $option, 'label' => ucfirst($option), 'sort_order' => $index + 1]);
            }
        }
        $second = $form->sections()->create(['key' => 'other', 'title' => 'Other section', 'sort_order' => 2]);
        $second->questions()->create(['form_version_id' => $form->id, 'key' => 'other_question', 'label' => 'Other question', 'type' => 'short_text', 'is_required' => false, 'sort_order' => 1, 'validation_rules' => []]);
        $document = $requirements->requirements()->create(['key' => 'second_document', 'label' => 'Second document', 'instructions' => 'Second instruction', 'is_required' => false, 'minimum_evidence' => 0, 'maximum_evidence' => 2, 'sort_order' => 2]);
        $document->providers()->attach($provider);
    }
    $fixture = ['form' => $form->id, 'requirements' => $requirements->id, 'builder' => '/admin/form-builder/'.$form->id.'/'.$requirements->id];
} else {
    $fixture = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $form = FormVersion::findOrFail($fixture['form']);
    $requirements = RequirementSetVersion::findOrFail($fixture['requirements']);
    if ($action === 'publish') {
        $publisher = app(QuestionnairePublicationService::class);
        $approval = $publisher->review($admin, $publisher->submitForReview($osds, $form, $requirements), true, 'SYNTHETIC-BUILDER');
        foreach (PublicationManifest::where('module_id', $requirements->module_id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->get() as $old) {
            $publisher->retire($admin, $old, 'Isolated builder browser test');
        }
        $manifest = $publisher->publishApplicantSandbox($admin, $approval);
        $region = Institution::where('official_id', 'SYN-001')->firstOrFail()->primary_region_id;
        $organization = Institution::create(['name' => 'Synthetic builder school', 'primary_region_id' => $region, 'is_synthetic' => true, 'organization_type' => 'hei']);
        InstitutionRepresentative::create(['institution_id' => $organization->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::create(['institution_id' => $organization->id, 'module_id' => $requirements->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated builder test']);
        $draft = app(ApplicantDraftService::class)->start($school, $organization, $manifest);
        $fixture['draft'] = '/applicant/drafts/'.$draft->public_id;
    } elseif ($action !== 'inspect') {
        if ($action === 'revoke-author') {
            $osds->removeRole(AccountRole::Osds->value);
        } elseif ($action === 'restore-author') {
            $osds->assignRole(AccountRole::Osds->value);
        } else {
            throw new RuntimeException('Unsupported isolated action.');
        }
    }
}
$form->refresh();
$requirements->refresh();
$fixture['questionRules'] = $form->questions()->pluck('condition_rules', 'key')->all();
$fixture['questionLabels'] = $form->questions()->pluck('label', 'key')->all();
$fixture['documentRules'] = $requirements->requirements()->pluck('condition_rules', 'key')->all();
$fixture['locks'] = [$form->lock_version, $requirements->lock_version];
$fixture['states'] = [$form->state, $requirements->state];
$fixture['reviewCount'] = ConfigurationReviewRequest::where('form_version_id', $form->id)->where('requirement_set_version_id', $requirements->id)->count();
$fixture['applicantCounts'] = [ApplicationDraft::count(), ApplicationDraftAnswer::count(), ApplicationSubmission::count()];
if (isset($fixture['draft'])) {
    $draft = ApplicationDraft::where('public_id', basename($fixture['draft']))->firstOrFail();
    $keys = $form->questions()->pluck('key', 'id');
    $fixture['savedAnswers'] = $draft->answers()->get()->mapWithKeys(fn ($answer) => [$keys[$answer->form_question_id] => $answer->value])->all();
    $fixture['applicableDocuments'] = $requirements->requirements()->get()->mapWithKeys(fn ($requirement) => [$requirement->key => app(EvidenceReferenceService::class)->applicable($draft, $requirement)])->all();
}
$fixture['sections'] = $form->sections()->with('questions.options')->get()->map(fn ($section) => [
    'key' => $section->key, 'title' => $section->title, 'description' => $section->description ?? '',
    'questions' => $section->questions->map(fn ($question) => [
        'key' => $question->key, 'label' => $question->label, 'type' => $question->type, 'is_required' => $question->is_required, 'help_text' => $question->help_text ?? '',
        'condition_rules' => $question->condition_rules, 'validation_rules' => $question->validation_rules ?? [],
        'options' => $question->options->map(fn ($option) => ['key' => $option->key, 'label' => $option->label])->all(),
    ])->all(),
])->all();
$fixture['documents'] = $requirements->requirements()->with('providers')->get()->map(fn ($requirement) => [
    'key' => $requirement->key, 'label' => $requirement->label, 'instructions' => $requirement->instructions ?? '',
    'is_required' => $requirement->is_required, 'minimum_evidence' => $requirement->minimum_evidence, 'maximum_evidence' => $requirement->maximum_evidence,
    'provider_ids' => $requirement->providers->pluck('id')->sort()->values()->all(), 'condition_rules' => $requirement->condition_rules,
])->all();
file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR));
echo "Isolated builder fixture completed: {$action}.\n";
