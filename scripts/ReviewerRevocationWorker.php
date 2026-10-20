<?php

use App\Models\AuditEvent;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Evidence\EvidenceReviewerAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
[$side, $run] = array_slice($argv, 1) + ['', ''];
$mode = $argv[3] ?? 'duplicate';
if (! in_array($mode, ['duplicate', 'inactive'], true)) {
    throw new RuntimeException('Isolated worker mode required.');
}
if (! in_array($side, ['A', 'B'], true) || ! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Isolated worker arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/reviewer-revocation.json')), true, flags: JSON_THROW_ON_ERROR);
$actor = User::findOrFail($data['actor']);
if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint002-')) {
    throw new RuntimeException('Isolated actor required.');
}
auth()->login($actor);
DB::select("SELECT set_config('application_name', ?, false)", ['gint002-'.$run.'-'.$side]);
$barrier = storage_path('app/private/gint002-'.$run);
$wait = function (string $path): void {
    $deadline = microtime(true) + 20;
    do {
        clearstatcache(true, $path);
        if (is_file($path)) {
            return;
        }
        usleep(1000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException('Isolated worker barrier expired.');
};
if ($side === 'A') {
    app()->instance(AuditRecorder::class, new class($barrier, $wait, $mode) extends AuditRecorder
    {
        public function __construct(private string $barrier, private Closure $wait, private string $mode) {}

        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $audit = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === 'evidence.reviewer_assignment_revoked') {
                file_put_contents($this->barrier.'-locked', 'ready');
                ($this->wait)($this->barrier.'-attempt');
                $name = basename($this->barrier).'-B';
                $deadline = microtime(true) + 15;
                do {
                    if (DB::selectOne("SELECT EXISTS(SELECT 1 FROM pg_stat_activity WHERE application_name = ? AND wait_event_type = 'Lock') AS waiting", [$name])->waiting) {
                        file_put_contents($this->barrier.'-blocked', 'verified');
                        if ($this->mode === 'inactive') {
                            // Disposable fixture only: change status while the
                            // other real connection waits for this row lock.
                            DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);
                        }

                        return $audit;
                    }
                    usleep(1000);
                } while (microtime(true) < $deadline);
                throw new RuntimeException('Second transaction did not wait on the locked assignment.');
            }

            return $audit;
        }
    });
} else {
    $wait($barrier.'-locked');
    file_put_contents($barrier.'-attempt', 'ready');
}
try {
    app(EvidenceReviewerAssignmentService::class)->revoke($actor, SyntheticEvidenceReviewerAssignment::findOrFail($data['assignments']['concurrent']), 'Concurrent synthetic '.$side);
    echo "committed\n";
} catch (ValidationException) {
    echo "already-revoked\n";
} catch (AuthorizationException) {
    echo "denied\n";
}
