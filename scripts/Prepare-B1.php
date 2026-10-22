<?php

use App\Models\Application;
use App\Models\ApplicationDecisionStage;
use App\Models\User;
use App\Services\Applications\ApplicationDecisionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\B1Scenario;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    throw new RuntimeException('Approved disposable database required.');
}
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/b1-scenario.json');
if ($mode === 'migrate') {
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_22_100000_create_synthetic_application_decision_stages.php', '--force' => true]);
    echo json_encode(['database' => DB::connection()->getDatabaseName(), 'output' => Artisan::output()]);
    exit;
}
if (in_array($mode, ['prepare', 'browser'], true)) {
    $s = B1Scenario::create($argv[2] ?? 'registration');
    $data = ['case' => $s['case']->id, 'public_id' => $s['case']->public_id, 'submission' => $s['submission']->id, 'module' => $s['module']->code,
        'applicant' => ['id' => $s['applicant']->id, 'email' => $s['applicant']->email],
        'staff' => collect($s['staff'])->map(fn ($a) => ['id' => $a->id, 'email' => $a->email])->all()];
    if ($mode === 'prepare') {
        foreach (['evaluator' => 'evaluated', 'chief' => 'recommended'] as $role => $action) {
            $actor = $s['staff'][$role];
            auth()->login($actor);
            $case = $s['case']->fresh();
            app(ApplicationDecisionService::class)->act($actor, $case, $case->current_submission_id, $case->lock_version, $case->decisionStages()->max('id'), (string) Str::uuid(), $action, ['summary' => 'Synthetic concurrency prerequisite', 'attested' => true]);
        }
    }
    $case = $s['case']->fresh();
    $data += ['version' => $case->lock_version, 'predecessor' => $case->decisionStages()->max('id')];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode($data);
    exit;
}
$data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
$case = Application::findOrFail($data['case']);
if (! User::findOrFail($data['applicant']['id'])->is_synthetic) {
    throw new RuntimeException('Synthetic case required.');
}
if ($mode === 'blocked') {
    echo json_encode(['blocked' => DB::selectOne('SELECT cardinality(pg_blocking_pids(?))>0 AS blocked', [(int) ($argv[2] ?? 0)])->blocked]);
} elseif ($mode === 'revoke') {
    User::findOrFail($data['staff']['evaluator']['id'])->revokePermissionTo('applications.evaluation.record');
    echo json_encode(['revoked' => true]);
} elseif ($mode === 'inspect') {
    echo json_encode(['state' => $case->state, 'final_decision_id' => $case->final_decision_id, 'stages' => ApplicationDecisionStage::where('application_id', $case->id)->get(['id', 'submission_id', 'stage_role', 'action', 'actor_id'])->toArray(), 'audits' => DB::table('audit_events')->where('resource_type', ApplicationDecisionStage::class)->whereIn('resource_id', ApplicationDecisionStage::where('application_id', $case->id)->pluck('id')->map(fn ($id) => (string) $id))->orderBy('id')->pluck('action')->all()]);
} else {
    throw new RuntimeException('Unknown B1 command.');
}
