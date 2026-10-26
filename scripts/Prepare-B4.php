<?php

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\IssuanceRequest;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
function b4Guard(): void
{
    if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test' || DB::selectOne('select current_database() as name')->name !== 'osds_ui_browser_test') {
        throw new RuntimeException('B4 requires the configured and actual isolated browser database.');
    }
}
b4Guard();
$file = storage_path('app/private/b4/fixtures.json');
$mode = $argv[1] ?? 'inspect';
if (in_array($mode, ['grant-chief-probe', 'revoke-chief-probe', 'revoke-chief', 'restore-chief'], true)) {
    b4Guard();
    $fixture = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $label = str_contains($mode, 'probe') ? 'evaluator' : 'chief';
    $actor = User::findOrFail($fixture['accounts'][$label]['id']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, $fixture['namespace'].'_')) {
        throw new RuntimeException('Only B4 namespace accounts may be controlled.');
    }
    b4Guard();
    if (in_array($mode, ['grant-chief-probe', 'restore-chief'], true)) {
        $actor->givePermissionTo('applications.recommendations.record');
    } else {
        $actor->revokePermissionTo('applications.recommendations.record');
    }
    echo json_encode(['database' => DB::selectOne('select current_database() as name')->name, 'mode' => $mode, 'actor' => $actor->id]);
    exit;
}
if (($argv[1] ?? 'inspect') !== 'prepare') {
    $fixture = is_file($file) ? json_decode(file_get_contents($file), true) : null;
    $caseIds = Application::whereIn('institution_id', $fixture['institutions'] ?? [])->pluck('id');
    $requests = IssuanceRequest::whereIn('application_id', $caseIds)->get();
    $documents = DemonstrationDocument::whereIn('request_id', $requests->pluck('id'))->get()->map(function ($doc) {
        $path = storage_path('app/private/'.$doc->storage_key);

        return $doc->toArray() + ['actual_checksum' => is_file($path) ? hash_file('sha256', $path) : null, 'attempt_count' => DB::table('demonstration_document_attempts')->where('document_id', $doc->id)->count(), 'history_count' => DB::table('demonstration_document_history')->where('document_id', $doc->id)->count()];
    });
    echo json_encode(['database' => DB::selectOne('select current_database() as name')->name, 'fixtures' => $fixture, 'reviews' => DB::table('configuration_review_requests')->where('submitted_by', $fixture['accounts']['osds']['id'] ?? 0)->get(), 'forms' => FormVersion::whereHas('definition', fn ($q) => $q->where('owner_id', $fixture['accounts']['osds']['id'] ?? 0))->with('questions')->get(), 'requests' => $requests, 'documents' => $documents, 'versions' => DemonstrationDocumentVersion::where('created_by', $fixture['accounts']['osds']['id'] ?? 0)->get(), 'cases' => Application::whereIn('institution_id', $fixture['institutions'] ?? [])->with(['submissions', 'decisionStages'])->get(), 'active_publications' => PublicationManifest::where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->get(['id', 'module_id', 'form_version_id'])]);
    exit;
}
if (is_file($file)) {
    throw new RuntimeException('Existing B4 ledger must be resumed, never overwritten.');
}
$namespace = 'b4_20261026_01';
if (User::where('email', 'like', $namespace.'%')->exists()) {
    throw new RuntimeException('Namespace already exists; inspect before recovery.');
}
$data = DB::transaction(function () use ($namespace): array {
    b4Guard();
    auth()->logout();
    $modules = OsdsModule::whereIn('code', ['registration', 'acsa'])->where('is_active', true)->get()->keyBy('code');
    if ($modules->count() !== 2) {
        throw new RuntimeException('Existing active module references required.');
    }
    $read = ['platform.admin.access', 'applications.intake.view', 'applications.intake.answers.view', 'applications.screening.history.view'];
    $config = ['platform.admin.access', 'questionnaires.view', 'questionnaires.publication.view'];
    $grants = [
        'osds' => array_merge($read, $config, ['questionnaires.draft.manage', 'questionnaires.review.submit', 'questionnaires.preview.sandbox', 'applicant.sandbox.publish', 'questionnaires.retire', 'forms.downloads.manage', 'issuance.definitions.manage', 'documents.templates.manage', 'documents.personnel.manage', 'documents.view', 'documents.generate', 'documents.download', 'documents.recover']),
        'reviewer' => array_merge($config, ['questionnaires.review', 'issuance.definitions.review', 'issuance.definitions.publish', 'documents.templates.review', 'documents.templates.publish', 'documents.personnel.review', 'documents.personnel.publish']),
        'evaluator' => array_merge($read, ['applications.screening.start', 'applications.corrections.request', 'applications.evaluation.record', 'applications.intake.evidence.open']),
        'chief' => array_merge($read, ['applications.recommendations.record']),
        'director' => array_merge($read, ['applications.decisions.record']),
        'applicant' => ['applicant.portal.access', 'applications.draft.create', 'applications.draft.view', 'applications.draft.update', 'evidence.references.manage', 'evidence.references.open.own', 'applications.submit', 'applications.track.own', 'applications.corrections.view.own', 'applications.resubmit', 'issuance.requests.submit'],
    ];
    foreach (['south', 'cor_only', 'acsa_only'] as $probe) {
        $grants[$probe] = array_merge($read, ['applications.screening.start', 'applications.evaluation.record', 'applications.recommendations.record', 'applications.decisions.record', 'documents.view', 'documents.generate', 'documents.download']);
    }
    $accounts = [];
    foreach ($grants as $label => $permissions) {
        b4Guard();
        foreach (array_unique($permissions) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $actor = User::factory()->create(['name' => $namespace.' '.$label, 'email' => $namespace.'_'.$label.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
        $actor->givePermissionTo(array_values(array_unique($permissions)));
        $accounts[$label] = $actor;
    }
    $regions = [];
    foreach (['north', 'south'] as $label) {
        b4Guard();
        $regions[$label] = Region::create(['code' => $namespace.'_'.$label, 'name' => 'Fictional B4 '.ucfirst($label)]);
    }
    foreach (['osds', 'evaluator', 'chief', 'director', 'south', 'cor_only', 'acsa_only'] as $label) {
        $region = $regions[$label === 'south' ? 'south' : 'north'];
        b4Guard();
        UserRegionAssignment::create(['user_id' => $accounts[$label]->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
        foreach ($modules as $code => $module) {
            if (($label === 'cor_only' && $code !== 'registration') || ($label === 'acsa_only' && $code !== 'acsa')) {
                continue;
            }
            b4Guard();
            SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $accounts[$label]->id, 'region_id' => $region->id, 'module_id' => $module->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'granted_by' => $accounts['osds']->id, 'reason' => 'Approved isolated B4 foundational fixture']);
        }
    }
    $institutions = [];
    foreach (['primary', 'auxiliary'] as $label) {
        b4Guard();
        $institution = Institution::create(['name' => $namespace.' Fictional HEI '.$label, 'primary_region_id' => $regions['north']->id, 'organization_type' => 'hei', 'is_synthetic' => true]);
        InstitutionRepresentative::create(['user_id' => $accounts['applicant']->id, 'institution_id' => $institution->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $accounts['osds']->id]);
        foreach ($modules as $module) {
            b4Guard();
            ApplicantSandboxEligibilityGrant::create(['institution_id' => $institution->id, 'module_id' => $module->id, 'granted_by' => $accounts['osds']->id, 'starts_at' => now()->subDay(), 'reason' => 'Approved isolated B4 foundational fixture']);
        }
        $institutions[$label] = $institution->id;
    }

    return ['namespace' => $namespace, 'database' => 'osds_ui_browser_test', 'accounts' => collect($accounts)->map(fn ($u) => $u->only(['id', 'email']))->all(), 'regions' => collect($regions)->map->id->all(), 'institutions' => $institutions, 'modules' => $modules->map->id->all()];
});
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($data);
