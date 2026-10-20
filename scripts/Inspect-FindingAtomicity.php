<?php

use App\Models\AuditEvent;
use App\Models\SyntheticEvidenceReviewCase;
use App\Models\SyntheticEvidenceReviewFinding;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$data = json_decode(file_get_contents(storage_path('app/private/chedro-journey.json')), true, flags: JSON_THROW_ON_ERROR);
$reviewer = User::findOrFail($data['reviewerId']);
if (! $reviewer->is_synthetic || ! str_starts_with($reviewer->email, 'chedro-d-')) {
    throw new RuntimeException('Disposable reviewer required.');
}
$case = SyntheticEvidenceReviewCase::where('public_id', $data['cases'][0])->firstOrFail();
$findings = SyntheticEvidenceReviewFinding::whereIn('case_requirement_id', $case->requirements()->select('id'))->get();
$paired = AuditEvent::where('action', 'evidence.test_finding_created')
    ->where('resource_type', (new SyntheticEvidenceReviewFinding)->getMorphClass())
    ->whereIn('resource_id', $findings->map(fn ($finding): string => (string) $finding->id))->count();
file_put_contents(storage_path('app/private/finding-atomicity.json'), json_encode([
    'findings' => $findings->count(), 'audits' => $paired,
    'copy_digest' => hash('sha256', json_encode($case->getRawOriginal(), JSON_THROW_ON_ERROR)),
    'timezone' => DB::selectOne('SHOW timezone')->TimeZone,
], JSON_THROW_ON_ERROR));
echo "Isolated atomicity counts recorded.\n";
