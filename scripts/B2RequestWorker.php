<?php

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\IssuanceDefinition;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Documents\CaseIssuanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    throw new RuntimeException('Approved worker database required.');
}
[$mode,$run] = array_slice($argv, 1) + ['', ''];
if (! in_array($mode, ['A', 'B', 'representation', 'permission', 'withdrawal'], true) || ! Str::isUuid($run)) {
    throw new RuntimeException('Controlled worker arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/b2-scenario.json')), true, flags: JSON_THROW_ON_ERROR);
$actor = User::findOrFail($data['applicant']['id']);
if (! $actor->is_synthetic || ! str_ends_with($actor->email, '.invalid')) {
    throw new RuntimeException('Disposable actor required.');
}auth()->login($actor);
$barrier = storage_path('app/private/b2-'.$run);
$pid = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
file_put_contents($barrier.'-'.$mode.'-started', json_encode(['pid' => $pid]));
$wait = static function () use ($barrier): void {
    $end = microtime(true) + 45;
    while (! is_file($barrier.'-release')) {
        if (microtime(true) > $end) {
            throw new RuntimeException('B2 barrier expired.');
        }usleep(1000);
    }
};
if (! in_array($mode, ['A', 'B'], true)) {
    DB::transaction(function () use ($mode, $actor, $data, $barrier, $pid, $wait) {
        if ($mode === 'representation') {
            DB::table('institution_representatives')->where('user_id', $actor->id)->update(['revoked_at' => now(), 'revoked_by' => $data['manager']['id']]);
        } elseif ($mode === 'permission') {
            $actor->revokePermissionTo('issuance.requests.submit');
        } else {
            $manager = User::findOrFail($data['manager']['id']);
            auth()->login($manager);
            $definition = IssuanceDefinition::findOrFail($data['definition']);
            app(CaseIssuanceService::class)->transition($manager, $definition, $definition->lock_version, 'withdrawn');
        }
        file_put_contents($barrier.'-ready', json_encode(['pid' => $pid]));
        $wait();
    });
    echo json_encode(['mode' => $mode, 'outcome' => 'changed']);
    exit;
}
if ($mode === 'A') {
    app()->instance(AuditRecorder::class, new class($barrier, $pid, $wait) extends AuditRecorder
    {
        public function __construct(private string $barrier, private int $pid, private Closure $wait) {}

        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $row = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === 'issuance.request_submitted') {
                file_put_contents($this->barrier.'-ready', json_encode(['pid' => $this->pid, 'depth' => DB::transactionLevel()]));
                ($this->wait)();
            }

            return $row;
        }
    });
}
try {
    app(CaseIssuanceService::class)->persist($actor, Application::findOrFail($data['case']), $data['definition'], 0, (string) Str::uuid(), ['contact_note' => 'Concurrent '.$mode], true);
    $outcome = 'committed';
} catch (AuthorizationException) {
    $outcome = 'denied';
} catch (ValidationException) {
    $outcome = 'rejected';
}
echo json_encode(['mode' => $mode, 'outcome' => $outcome]);
