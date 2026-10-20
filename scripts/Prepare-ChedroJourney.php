<?php

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\DocumentaryRequirement;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\PublicationManifest;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\SyntheticEvidenceReviewFinding;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Support\AccountRole;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// All setup and mutations are pinned by the existing browser fixture bootstrap.
$action = $argv[1] ?? 'prepare';
$argv[1] = 'prepare';
if ($action === 'prepare') {
    require __DIR__.'/Prepare-ApplicantJourney.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
    foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $name => $value) {
        putenv($name.'='.$value);
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
    if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
        throw new RuntimeException('Refusing cached configuration.');
    }
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test') {
    throw new RuntimeException('Browser database only.');
}
$file = storage_path('app/private/chedro-journey.json');
if ($action === 'prepare') {
    $fixture = json_decode(file_get_contents(storage_path('app/private/applicant-journey.json')), true, flags: JSON_THROW_ON_ERROR);
    $admin = User::where('email', 'admin@example.invalid')->firstOrFail();
    $school = User::where('email', 'school@example.invalid')->firstOrFail();
    $manifest = PublicationManifest::findOrFail($fixture['manifest']);
    $region = Region::create(['code' => 'D-'.Str::upper(Str::random(10)), 'name' => 'Synthetic Batch D region'])->id;
    $reviewer = User::factory()->create(['email' => 'chedro-d-'.Str::lower(Str::random(10)).'@example.invalid', 'name' => 'Synthetic regional reviewer', 'status' => 'active', 'is_synthetic' => true, 'password' => Hash::make('UiBrowserOnly-2026!')]);
    $reviewer->assignRole(AccountRole::Chedro->value);
    DB::table('user_region_assignments')->insert(['user_id' => $reviewer->id, 'region_id' => $region, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
    foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignment) {
        $assignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $region, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id, 'reason' => 'Isolated Batch D browser test']);
    }
    $fixture['reviewer'] = $reviewer->email;
    $fixture['reviewerId'] = $reviewer->id;
    foreach (['expired', 'noPermission'] as $kind) {
        $probe = User::factory()->create(['email' => 'chedro-d-'.strtolower(Str::random(10)).'@example.invalid', 'name' => 'Synthetic limited reviewer', 'status' => 'active', 'is_synthetic' => true, 'password' => Hash::make('UiBrowserOnly-2026!')]);
        if ($kind === 'expired') {
            $probe->assignRole(AccountRole::Chedro->value);
        } else {
            $probe->givePermissionTo('platform.admin.access');
        }
        DB::table('user_region_assignments')->insert(['user_id' => $probe->id, 'region_id' => $region, 'starts_at' => now()->subDays(3), 'granted_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignment) {
            $table = (new $assignment)->getTable();
            DB::table($table)->insert(['public_id' => (string) Str::uuid(), 'reviewer_id' => $probe->id, 'region_id' => $region, 'module_id' => $manifest->module_id, 'starts_at' => now()->subDays(3)->toIso8601String(), 'ends_at' => $kind === 'expired' ? now()->subMinute()->toIso8601String() : null, 'granted_by' => $admin->id, 'reason' => 'Isolated explicit-instant limited reviewer probe']);
            if ($kind === 'expired' && ! DB::table($table)->where('reviewer_id', $probe->id)->whereRaw('ends_at <= CURRENT_TIMESTAMP')->exists()) {
                throw new RuntimeException('Database did not confirm the expired browser assignment.');
            }
        }
        $fixture[$kind] = $probe->email;
    }
    $fixture['applications'] = [];
    $fixture['cases'] = [];
    for ($index = 1; $index <= 12; $index++) {
        $org = Institution::create(['name' => 'Batch D school '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'primary_region_id' => $region, 'organization_type' => 'hei', 'is_synthetic' => true]);
        InstitutionRepresentative::create(['institution_id' => $org->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::create(['institution_id' => $org->id, 'module_id' => $manifest->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated Batch D fixture']);
        $draft = app(ApplicantDraftService::class)->start($school, $org, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Exact Mixed_CASE answer '.$index, 'has_activity' => 'yes', 'details' => 'Synthetic activity', 'email' => 'synthetic@example.invalid'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), DocumentaryRequirement::findOrFail($fixture['requirement']), 'https://docs.google.com/document/d/SyntheticBatchDFile'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).'/edit', 2, (string) Str::uuid());
        $case = app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        $application = $draft->application;
        app(ApplicationLifecycleService::class)->submit($school, $application, 1, 3, (string) Str::uuid());
        $fixture['applications'][] = $application->public_id;
        $fixture['cases'][] = $case->public_id;
    }
} else {
    $fixture = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $application = Application::where('public_id', $fixture['applications'][0])->firstOrFail();
    if ($action === 'revoke') {
        foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignment) {
            foreach ($assignment::where('reviewer_id', $fixture['reviewerId'])->whereNull('revoked_at')->get() as $grant) {
                $grant->update(['revoked_at' => now(), 'revoked_by' => User::where('email', 'admin@example.invalid')->value('id'), 'revocation_reason' => 'Isolated browser probe']);
            }
        }
    } elseif ($action === 'restore') {
        foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignment) {
            $assignment::create(['reviewer_id' => $fixture['reviewerId'], 'region_id' => $application->routing_region_id, 'module_id' => $application->module_id, 'starts_at' => now()->subDay(), 'granted_by' => User::where('email', 'admin@example.invalid')->value('id'), 'reason' => 'Isolated queue screenshot retest after revocation probe']);
        }
    } elseif ($action === 'inspect') {
        $fixture['state'] = $application->state;
        $fixture['submissionCount'] = $application->submissions()->count();
        $fixture['correctionCount'] = $application->correctionRequests()->count();
        $fixture['originalAnswer'] = $application->submissions()->orderBy('submission_sequence')->first()->answers()->where('question_key', 'name')->value('value');
        $fixture['draftPath'] = '/applicant/drafts/'.$application->currentWorkingRevision?->draft?->public_id;
        $fixture['findingCount'] = SyntheticEvidenceReviewFinding::whereHas('reviewer', fn ($q) => $q->whereKey($fixture['reviewerId']))->count();
        $bound = DB::connection()->prepareBindings([now()])[0];
        $fixture['clockSkewSeconds'] = (float) DB::selectOne('SELECT abs(extract(epoch FROM (CAST(? AS timestamptz)-CURRENT_TIMESTAMP))) AS skew', [$bound])->skew;
        $fixture['sessionTimezone'] = DB::selectOne('SHOW TIME ZONE')->TimeZone;
        $fixture['findingDelaySeconds'] = DB::table('synthetic_evidence_review_findings as f')->join('synthetic_evidence_review_requirements as r', 'r.id', '=', 'f.case_requirement_id')->join('synthetic_evidence_review_cases as c', 'c.id', '=', 'r.case_id')->where('f.reviewer_id', $fixture['reviewerId'])->selectRaw('extract(epoch FROM (f.reviewed_at-c.created_at)) AS elapsed')->first()?->elapsed;
    } else {
        throw new RuntimeException('Unsupported isolated fixture action.');
    }
}
file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR));
echo "Isolated Batch D fixture action complete.\n";
