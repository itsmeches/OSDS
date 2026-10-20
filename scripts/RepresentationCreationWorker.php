<?php

use App\Models\AuditEvent;
use App\Models\InstitutionRepresentative;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RepresentationCreationService;
use App\Support\AccountRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
[$mode, $run] = array_slice($argv, 1) + ['', ''];
if (! in_array($mode, ['A', 'B', 'scope', 'permission', 'inactive', 'target-role', 'institution'], true) || ! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Isolated worker arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/representation-creation.json')), true, flags: JSON_THROW_ON_ERROR);
$actor = User::findOrFail($data['actor']);
if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint007-')) {
    throw new RuntimeException('Isolated fixture required.');
}
auth()->login($actor);
$barrier = storage_path('app/private/gint007-'.$run);
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
if (! in_array($mode, ['A', 'B'], true)) {
    DB::transaction(function () use ($mode, $actor, $data, $barrier, $wait, $pid): void {
        if ($mode === 'scope') {
            DB::table('user_region_assignments')->where('id', $data['scope'])->update(['revoked_at' => now()]);
        } elseif ($mode === 'permission') {
            $actor->revokePermissionTo('institution_representatives.assign');
        } elseif ($mode === 'target-role') {
            User::findOrFail($data['target'])->syncRoles([AccountRole::SuperUser->value]);
        } elseif ($mode === 'institution') {
            DB::table('institutions')->where('id', $data['institution'])->update(['primary_region_id' => $data['outsideRegion']]);
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
            $audit = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === InstitutionRepresentative::class.'.created') {
                file_put_contents($this->barrier.'-ready', json_encode(['pid' => $this->pid, 'transactionDepth' => DB::transactionLevel()]));
                ($this->wait)();
            }

            return $audit;
        }
    });
}
try {
    $row = app(RepresentationCreationService::class)->create($actor, ['user_id' => $data['target'], 'institution_id' => $data['institution'], 'starts_at' => now()->subMinute()->toIso8601String()]);
    $result = ['mode' => $mode, 'outcome' => 'created', 'id' => $row->id];
} catch (AuthorizationException) {
    $result = ['mode' => $mode, 'outcome' => 'denied'];
} catch (UniqueConstraintViolationException) {
    $result = ['mode' => $mode, 'outcome' => 'duplicate-rejected'];
}
echo json_encode($result)."\n";
