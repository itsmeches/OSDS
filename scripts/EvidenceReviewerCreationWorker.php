<?php

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Evidence\EvidenceReviewerCreationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
[$side, $run] = array_slice($argv, 1) + ['', ''];
if (! in_array($side, ['A', 'B'], true) || ! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Isolated worker arguments required.');
}
$data = json_decode(file_get_contents(storage_path('app/private/evidence-reviewer-creation.json')), true, flags: JSON_THROW_ON_ERROR);
$actor = User::findOrFail($data['actor']);
if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint005-')) {
    throw new RuntimeException('Isolated fixture required.');
}
auth()->login($actor);
$barrier = storage_path('app/private/gint005-'.$run);
app()->instance(AuditRecorder::class, new class($barrier, $side) extends AuditRecorder
{
    public function __construct(private string $barrier, private string $side) {}

    public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
    {
        $audit = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
        if ($action === 'evidence.reviewer_assignment_granted') {
            if (DB::transactionLevel() !== 1) {
                throw new RuntimeException('Operation transaction required.');
            }
            file_put_contents($this->barrier.'-'.$this->side, json_encode(['pid' => DB::selectOne('SELECT pg_backend_pid() AS pid')->pid, 'inTransaction' => true]));
            $deadline = microtime(true) + 20;
            do {
                if (is_file($this->barrier.'-release')) {
                    return $audit;
                }
                usleep(1000);
            } while (microtime(true) < $deadline);
            throw new RuntimeException('Isolated barrier expired.');
        }

        return $audit;
    }
});
$row = app(EvidenceReviewerCreationService::class)->create($actor, ['reviewer_id' => $data['reviewer'], 'region_id' => $data['region'], 'module_id' => $data['module'],
    'starts_at' => now()->subMinute()->toIso8601String(), 'reason' => 'Concurrent synthetic '.$side]);
echo json_encode(['side' => $side, 'committed' => true, 'id' => $row->id])."\n";
