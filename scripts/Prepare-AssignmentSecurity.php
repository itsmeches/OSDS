<?php

use App\Models\AuditEvent;
use App\Models\UserRegionAssignment;

// Reuse the existing isolated-account bootstrap and its hard database guard.
$assignmentAction = $argv[1] ?? 'prepare';
$argv[1] = $assignmentAction === 'prepare' ? 'prepare' : 'inspect';
require __DIR__.'/Prepare-AccountSecurity.php';
$assignmentFile = storage_path('app/private/assignment-security.json');
if ($assignmentAction === 'prepare') {
    $assignmentFixture = $fixture;
    foreach (['actor', 'target', 'restricted'] as $key) {
        $assignmentFixture[$key]['grant'] = UserRegionAssignment::where('user_id', $fixture[$key]['id'])->where('region_id', $fixture['region'])->sole()->id;
    }
} else {
    $assignmentFixture = json_decode(file_get_contents($assignmentFile), true, flags: JSON_THROW_ON_ERROR);
}
if ($assignmentAction === 'revoke-scope') {
    // Keep this browser session alive to exercise request-time scope checks
    // independently of the production revoke service's session invalidation.
    UserRegionAssignment::whereKey($assignmentFixture['actor']['grant'])->update(['revoked_at' => now()]);
}
foreach (['actor', 'target', 'restricted'] as $key) {
    $grant = UserRegionAssignment::findOrFail($assignmentFixture[$key]['grant']);
    $assignmentFixture[$key]['end'] = $grant->ends_at?->utc()->toDateTimeString();
    $assignmentFixture[$key]['audit_count'] = AuditEvent::where('resource_type', UserRegionAssignment::class)->where('resource_id', (string) $grant->id)->count();
}
file_put_contents($assignmentFile, json_encode($assignmentFixture, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
