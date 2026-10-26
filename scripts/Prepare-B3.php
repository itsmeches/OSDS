<?php

use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Models\IssuanceRequest;
use App\Models\User;
use App\Services\Documents\DemonstrationDocumentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\B3Scenario;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    throw new RuntimeException('Approved disposable database required');
}
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/b3-scenario.json');
config(['demonstration_documents.node' => sys_get_temp_dir().'/osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe']);
if ($mode === 'migrate') {
    Artisan::call('migrate', ['--force' => true]);
    echo json_encode(['database' => DB::connection()->getDatabaseName(), 'output' => Artisan::output()]);
    exit;
}
if ($mode === 'prepare') {
    $s = B3Scenario::create($argv[2] ?? 'registration', str_repeat('Fictional long supplementary detail. ', 50));
    $data = ['request' => $s['request']->id, 'case' => $s['case']->id, 'public_id' => $s['case']->public_id, 'module' => $s['module']->id, 'region' => $s['region']->id, 'template' => $s['template']->id, 'personnel' => $s['personnel']->id, 'operator' => ['id' => $s['operator']->id, 'email' => $s['operator']->email], 'manager' => ['id' => $s['manager']->id, 'email' => $s['manager']->email], 'applicant' => ['id' => $s['applicant']->id, 'email' => $s['applicant']->email]];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode($data);
    exit;
}
if ($mode === 'activity') {
    echo json_encode(DB::select("SELECT pid,datname,state,wait_event_type,wait_event FROM pg_stat_activity WHERE datname IN ('osds_test','osds_ui_browser_test')"));
    exit;
}
$data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
if ($mode === 'revoke-download') {
    User::findOrFail($data['operator']['id'])->revokePermissionTo('documents.download');
    echo json_encode(['revoked' => true]);
    exit;
}
if ($mode === 'revoke-representation') {
    DB::table('institution_representatives')->where('user_id', $data['applicant']['id'])->whereNull('revoked_at')->update(['revoked_at' => now(), 'revoked_by' => $data['manager']['id']]);
    echo json_encode(['revoked' => true]);
    exit;
}
if ($mode === 'request') {
    echo json_encode(IssuanceRequest::findOrFail($data['request'])->only(['public_id']));
    exit;
}
if ($mode === 'versions') {
    echo json_encode(['latestTemplate' => DemonstrationDocumentVersion::where(['module_id' => $data['module'], 'kind' => 'template'])->max('id')]);
    exit;
}
if ($mode === 'revoke') {
    User::findOrFail($data['operator']['id'])->revokePermissionTo('documents.generate');
    echo json_encode(['revoked' => true]);
    exit;
}
if ($mode === 'inactive') {
    User::findOrFail($data['operator']['id'])->update(['status' => 'inactive']);
    echo json_encode(['inactive' => true]);
    exit;
}
if ($mode === 'scope') {
    DB::table('synthetic_application_reviewer_assignments')->where('reviewer_id', $data['operator']['id'])->whereNull('revoked_at')->update(['revoked_at' => now(), 'revoked_by' => $data['manager']['id'], 'revocation_reason' => 'Disposable B3 authority revocation']);
    echo json_encode(['revoked' => true]);
    exit;
}
if ($mode === 'personnel') {
    $a = User::findOrFail($data['manager']['id']);
    auth()->login($a);
    $v = DemonstrationDocumentVersion::findOrFail($data['personnel']);
    app(DemonstrationDocumentService::class)->advanceVersion($a, $v->id, $v->lock_version, 'withdrawn');
    echo json_encode(['withdrawn' => true]);
    exit;
}
if ($mode === 'withdraw') {
    $a = User::findOrFail($data['manager']['id']);
    auth()->login($a);
    $v = DemonstrationDocumentVersion::findOrFail($data['template']);
    app(DemonstrationDocumentService::class)->advanceVersion($a, $v->id, $v->lock_version, 'withdrawn');
    echo json_encode(['withdrawn' => true]);
    exit;
}
if ($mode === 'inspect') {
    $docs = DemonstrationDocument::where('request_id', $data['request'])->get();
    echo json_encode(['documents' => $docs->map(fn ($d) => $d->only(['id', 'public_id', 'state', 'page_count', 'byte_size', 'checksum', 'request_id', 'submission_id', 'decision_id', 'template_id', 'personnel_id']))->all(), 'attempts' => DB::table('demonstration_document_attempts')->whereIn('document_id', $docs->pluck('id'))->count(), 'events' => DB::table('demonstration_document_history')->whereIn('document_id', $docs->pluck('id'))->orderBy('id')->pluck('event')->all()]);
    exit;
}
throw new RuntimeException('Unknown B3 mode');
