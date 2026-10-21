<?php

use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RegionCreationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
[$mode, $run] = array_slice($argv, 1) + ['', ''];
if (! in_array($mode, ['A', 'B', 'permission', 'inactive', 'role']) || ! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Isolated worker arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/region-creation.json')), true);
$actor = User::findOrFail($data[$mode === 'A' ? 'actor' : 'contender']);
if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'k5create-')) {
    throw new RuntimeException('Isolated K5 fixture required.');
}
auth()->login($actor);
$barrier = storage_path('app/private/k5create-'.$run);
$pid = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
file_put_contents($barrier.'-'.$mode.'-started', json_encode(['pid' => $pid]));
$wait = static function () use ($barrier): void {
    $deadline = microtime(true) + 25;
    while (! is_file($barrier.'-release')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Isolated barrier expired.');
        }
        usleep(1000);
    }
};
if (! in_array($mode, ['A', 'B'])) {
    DB::transaction(function () use ($mode, $actor, $barrier, $wait, $pid): void {
        if ($mode === 'permission') {
            $actor->revokePermissionTo('regions.manage');
        } elseif ($mode === 'role') {
            $actor->removeRole('Admin');
        } else {
            DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);
        }
        file_put_contents($barrier.'-ready', json_encode(['pid' => $pid]));
        $wait();
    });
    echo json_encode(['mode' => $mode, 'committed' => true])."\n";
    exit;
}
if ($mode === 'A') {
    app()->instance(AuditRecorder::class, new class($barrier, $wait, $pid) extends AuditRecorder
    {
        public function __construct(private string $barrier, private Closure $wait, private int $pid) {}

        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $row = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === Region::class.'.created') {
                file_put_contents($this->barrier.'-ready', json_encode(['pid' => $this->pid, 'transactionDepth' => DB::transactionLevel()]));
                ($this->wait)();
            }

            return $row;
        }
    });
}
try {
    app(RegionCreationService::class)->create($actor, ['code' => $data['code'], 'name' => $data['name'], 'is_active' => true]);
    $outcome = 'created';
} catch (AuthorizationException) {
    $outcome = 'denied';
} catch (ValidationException|UniqueConstraintViolationException) {
    $outcome = 'duplicate-rejected';
}
echo json_encode(['mode' => $mode, 'outcome' => $outcome])."\n";
