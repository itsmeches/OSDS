<?php

use App\Models\ApplicationDraft;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\PublicationManifest;
use App\Models\RequirementSetVersion;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use Database\Seeders\DevelopmentFixtureSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// Runs only against this reserved disposable database. Does not reset or drop databases.
require __DIR__.'/../vendor/autoload.php';
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Browser verification requires uncached configuration.');
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.pgsql');
if (config('database.default') !== 'pgsql' || $database['database'] !== 'osds_ui_browser_test') {
    throw new RuntimeException('Refusing to provision a non-browser database.');
}
$pdo = new PDO('pgsql:host='.$database['host'].';port='.$database['port'].';dbname=postgres', $database['username'], $database['password']);
if (! $pdo->query("SELECT 1 FROM pg_database WHERE datname = 'osds_ui_browser_test'")->fetchColumn()) {
    $pdo->exec('CREATE DATABASE osds_ui_browser_test');
}
putenv('OSDS_FIXTURE_PASSWORD=UiBrowserOnly-2026!');
$_ENV['OSDS_FIXTURE_PASSWORD'] = $_SERVER['OSDS_FIXTURE_PASSWORD'] = 'UiBrowserOnly-2026!';
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => DevelopmentFixtureSeeder::class, '--force' => true]);

$school = User::where('email', 'school@example.invalid')->firstOrFail();
$osds = User::where('email', 'osds@example.invalid')->firstOrFail();
$manifest = PublicationManifest::where('channel', 'applicant_sandbox')->whereHas('module', fn ($query) => $query->where('code', 'registration'))->whereDoesntHave('retirement')->firstOrFail();
$institution = Institution::where('official_id', 'SYN-001')->firstOrFail();
$draft = ApplicationDraft::where('created_by', $school->id)->where('publication_manifest_id', $manifest->id)->first();
$draft ??= app(ApplicantDraftService::class)->start($school, $institution, $manifest);
$drafts = app(QuestionnaireDraftService::class);
$form = FormVersion::where('state', 'draft')->whereHas('definition', fn ($query) => $query->where('module_id', $manifest->module_id))->first();
$requirements = RequirementSetVersion::where('state', 'draft')->where('module_id', $manifest->module_id)->first();
$form ??= $drafts->cloneForm($osds, $manifest->formVersion);
$requirements ??= $drafts->cloneRequirements($osds, $manifest->requirementSetVersion);
$fixtures = ['draft' => '/applicant/drafts/'.$draft->public_id, 'builder' => '/admin/form-builder/'.$form->id.'/'.$requirements->id];
$reviewManifest = PublicationManifest::where('channel', 'applicant_sandbox')->whereHas('module', fn ($query) => $query->where('code', 'acsa'))->whereDoesntHave('retirement')->firstOrFail();
$reviewDraft = ApplicationDraft::where('created_by', $school->id)->where('publication_manifest_id', $reviewManifest->id)->first();
if (! $reviewDraft) {
    $reviewDraft = app(ApplicantDraftService::class)->start($school, $institution, $reviewManifest);
    $answers = [];
    foreach ($reviewManifest->formVersion->sections->flatMap->questions as $question) {
        $answers[$question->key] = match ($question->type) {
            'single_choice' => $question->options->first()->key,
            'multiple_choice' => [$question->options->first()->key],
            'email' => 'synthetic-ui@example.invalid',
            'integer' => 3,
            'decimal' => 3.5,
            'date' => '2026-10-19',
            'yes_no', 'declaration' => 'yes',
            default => 'Synthetic browser sample',
        };
    }
    app(ApplicantDraftService::class)->save($school, $reviewDraft, $answers, 1, (string) Str::uuid());
    $reviewDraft->refresh();
    $evidence = app(EvidenceReferenceService::class);
    foreach ($reviewManifest->requirementSetVersion->requirements as $index => $requirement) {
        if ($evidence->applicable($reviewDraft, $requirement)) {
            for ($count = 0; $count < $requirement->minimum_evidence; $count++) {
                $evidence->add($school, $reviewDraft, $requirement, 'https://drive.google.com/file/d/SyntheticUiFile'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).$count.'/view', $reviewDraft->lock_version, (string) Str::uuid());
                $reviewDraft->refresh();
            }
        }
    }
    $copy = app(SyntheticEvidenceReviewService::class)->createCopy($school, $reviewDraft, $reviewDraft->lock_version, (string) Str::uuid());
    $application = $reviewDraft->application;
    app(ApplicationLifecycleService::class)->submit($school, $application, $application->lock_version, $reviewDraft->lock_version, (string) Str::uuid());
}
$application = $reviewDraft->application;
if (in_array($application->state, ['submitted', 'resubmitted'], true)) {
    $reviewer = User::where('email', 'chedro@example.invalid')->firstOrFail();
    app(ApplicationLifecycleService::class)->beginScreening($reviewer, $application);
}
$copy = SyntheticEvidenceReviewCase::where('application_draft_id', $reviewDraft->id)->firstOrFail();
$fixtures['intake'] = '/admin/synthetic-application-intake?application='.$application->public_id;
$fixtures['evidenceReview'] = '/admin/synthetic-evidence-reviews?case='.$copy->public_id;
file_put_contents(storage_path('app/private/ui-browser-fixtures.json'), json_encode($fixtures, JSON_THROW_ON_ERROR));
echo "Isolated browser database ready: osds_ui_browser_test. No manual-testing database modified.\n";
