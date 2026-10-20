<?php

use App\Models\AuditEvent;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use Illuminate\Database\Eloquent\Model;

$side = $argv[1] ?? '';
$run = $argv[2] ?? '';
if (! in_array($side, ['A', 'B'], true) || ! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Isolated barrier arguments required.');
}
require __DIR__.'/Bootstrap-FindingAtomicity.php';
$data = json_decode(file_get_contents(storage_path('app/private/chedro-journey.json')), true, flags: JSON_THROW_ON_ERROR);
$reviewer = User::findOrFail($data['reviewerId']);
if (! $reviewer->is_synthetic || ! str_starts_with($reviewer->email, 'chedro-d-')) {
    throw new RuntimeException('Disposable reviewer required.');
}
$case = SyntheticEvidenceReviewCase::where('public_id', $data['cases'][0])->firstOrFail();
$barrier = storage_path('app/private/g-int001-'.$run);
app()->instance(AuditRecorder::class, new class($barrier, $side) extends AuditRecorder
{
    public function __construct(private string $barrier, private string $side) {}

    public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
    {
        $audit = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
        if ($action === 'evidence.test_finding_created') {
            file_put_contents($this->barrier.'-'.$this->side, 'ready');
            $other = $this->barrier.'-'.($this->side === 'A' ? 'B' : 'A');
            $deadline = microtime(true) + 15;
            // Test synchronization only: both real PostgreSQL transactions have
            // inserted their own pair before either is allowed to commit.
            do {
                clearstatcache(true, $other);
                if (is_file($other)) {
                    return $audit;
                }
                usleep(1000);
            } while (microtime(true) < $deadline);
            throw new RuntimeException('Isolated concurrent worker did not arrive.');
        }

        return $audit;
    }
});
app(SyntheticEvidenceReviewService::class)->finding($reviewer, $case->requirements()->firstOrFail(), null, 'completeness', 'not_reviewed', 'Concurrent synthetic '.$side);
echo "Isolated finding worker committed.\n";
