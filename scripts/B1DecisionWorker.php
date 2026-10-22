<?php

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Applications\ApplicationDecisionService;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
[$mode, $run] = array_slice($argv, 1) + ['', ''];
if (! in_array($mode, ['A', 'B', 'permission', 'inactive', 'assignment', 'scope'], true) || ! Str::isUuid($run)) {
    throw new RuntimeException('Isolated arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/b1-scenario.json')), true, flags: JSON_THROW_ON_ERROR);
$actor = User::findOrFail($data['staff'][$mode === 'A' ? 'director' : 'contender']['id']);
if (! $actor->is_synthetic || ! str_ends_with($actor->email, '.invalid')) {
    throw new RuntimeException('Disposable actor required.');
}
auth()->login($actor);
$barrier = storage_path('app/private/b1-'.$run);
$pid = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
file_put_contents($barrier.'-'.$mode.'-started', json_encode(['pid' => $pid]));
$wait = static function () use ($barrier): void {
    $end = microtime(true) + 40;
    while (! is_file($barrier.'-release')) {
        if (microtime(true) > $end) {
            throw new RuntimeException('Barrier expired.');
        } usleep(1000);
    }
};
if (! in_array($mode, ['A', 'B'], true)) {
    DB::transaction(function () use ($mode, $actor, $barrier, $wait, $pid, $data): void {
        if ($mode === 'permission') {
            $actor->revokePermissionTo('applications.decisions.record');
        } elseif ($mode === 'inactive') {
            $actor->update(['status' => 'inactive']);
        } elseif ($mode === 'assignment') {
            DB::table('synthetic_application_reviewer_assignments')->where('reviewer_id', $actor->id)->update(['revoked_at' => now(), 'revoked_by' => $data['staff']['director']['id'], 'revocation_reason' => 'Disposable worker revocation']);
        } else {
            DB::table('user_region_assignments')->where('user_id', $actor->id)->update(['ends_at' => now()->subHour()]);
        }
        file_put_contents($barrier.'-ready', json_encode(['pid' => $pid]));
        $wait();
    });
    echo json_encode(['mode' => $mode, 'outcome' => 'authority_changed']);
    exit;
}
if ($mode === 'A') {
    app()->instance(AuditRecorder::class, new class($barrier, $wait, $pid) extends AuditRecorder
    {
        public function __construct(private string $barrier, private Closure $wait, private int $pid) {}

        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $row = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === 'application.approved') {
                file_put_contents($this->barrier.'-ready', json_encode(['pid' => $this->pid, 'depth' => DB::transactionLevel()]));
                ($this->wait)();
            }

            return $row;
        }
    });
}
try {
    app(ApplicationDecisionService::class)->act($actor, Application::findOrFail($data['case']), $data['submission'], $data['version'], $data['predecessor'], (string) Str::uuid(), $mode === 'A' ? 'approved' : 'rejected', ['summary' => 'Synthetic worker outcome']);
    $outcome = 'committed';
} catch (AuthorizationException) {
    $outcome = 'denied';
} catch (ValidationException) {
    $outcome = 'stale';
}
echo json_encode(['mode' => $mode, 'outcome' => $outcome]);
