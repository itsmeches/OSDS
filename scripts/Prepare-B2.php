<?php

use App\Models\IssuanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\B2Scenario;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    throw new RuntimeException('Approved disposable database required.');
}
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/b2-scenario.json');
if ($mode === 'migrate') {
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_22_200000_create_case_issuance_requests.php', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_22_200001_enforce_issuance_snapshot_bindings.php', '--force' => true]);
    echo json_encode(['database' => DB::connection()->getDatabaseName(), 'output' => Artisan::output()]);
    exit;
}
if (in_array($mode, ['prepare', 'browser'], true)) {
    $s = B2Scenario::create($argv[2] ?? 'registration', publish: $mode === 'prepare');
    $data = ['case' => $s['case']->id, 'public_id' => $s['case']->public_id, 'submission' => $s['submission']->id, 'decision' => $s['case']->final_decision_id, 'definition' => $s['definition']?->id, 'applicant' => ['id' => $s['applicant']->id, 'email' => $s['applicant']->email], 'manager' => ['id' => $s['manager']->id, 'email' => $s['manager']->email], 'input' => $s['input']];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode($data);
    exit;
}
$data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
if (! User::findOrFail($data['applicant']['id'])->is_synthetic) {
    throw new RuntimeException('Synthetic case required.');
}
if ($mode === 'blocked') {
    echo json_encode(['blocked' => DB::selectOne('SELECT cardinality(pg_blocking_pids(?))>0 AS blocked', [(int) ($argv[2] ?? 0)])->blocked]);
} elseif ($mode === 'revoke') {
    User::findOrFail($data['applicant']['id'])->revokePermissionTo('issuance.requests.submit');
    echo json_encode(['revoked' => true]);
} elseif ($mode === 'inspect') {
    $rows = IssuanceRequest::where('application_id', $data['case'])->get();
    echo json_encode(['requests' => $rows->map(fn ($r) => $r->only(['id', 'state', 'decision_id', 'submission_id', 'definition_id', 'certificate_type', 'identity_snapshot', 'answers', 'lock_version']))->all(), 'audits' => DB::table('audit_events')->where('resource_type', IssuanceRequest::class)->whereIn('resource_id', $rows->pluck('id')->map(fn ($id) => (string) $id))->orderBy('id')->pluck('action')->all(), 'history' => DB::table('issuance_history')->whereIn('request_id', $rows->pluck('id'))->count()]);
} else {
    throw new RuntimeException('Unknown B2 mode.');
}
