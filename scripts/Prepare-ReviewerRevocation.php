<?php

use App\Models\AuditEvent;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Support\AccountRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$action = $argv[1] ?? 'inspect';
$file = storage_path('app/private/reviewer-revocation.json');
if ($action === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $actor = User::factory()->create(['name' => 'Synthetic revocation manager', 'email' => 'gint002-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->assignRole(AccountRole::Admin->value);
    auth()->login($actor);
    $region = Region::create(['code' => 'G2-'.$suffix, 'name' => 'Isolated revocation region']);
    $grant = DB::table('user_region_assignments')->insertGetId(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subMinute()->toIso8601String(), 'granted_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
    $reviewer = User::factory()->create(['name' => 'Synthetic revocation reviewer '.$suffix, 'is_synthetic' => true]);
    $reviewer->assignRole(AccountRole::Chedro->value);
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'reviewerName' => $reviewer->name, 'grant' => $grant, 'assignments' => []];
    foreach (['failure', 'success', 'lost', 'scope', 'permission', 'concurrent'] as $key) {
        $assignment = SyntheticEvidenceReviewerAssignment::create(['reviewer_id' => $reviewer->id, 'region_id' => $region->id, 'module_id' => OsdsModule::firstOrFail()->id, 'starts_at' => now()->subMinute(), 'reason' => 'Synthetic '.$key.' assignment']);
        $data['assignments'][$key] = $assignment->id;
    }
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint002-')) {
        throw new RuntimeException('Isolated fixture required.');
    }
    if ($action === 'revoke-scope') {
        DB::table('user_region_assignments')->where('id', $data['grant'])->update(['revoked_at' => now()->toIso8601String()]);
    } elseif ($action === 'revoke-permission') {
        $remaining = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === 'evidence.reviewer.assignments.manage')->all();
        $actor->syncRoles([]);
        $actor->syncPermissions($remaining);
    } elseif ($action !== 'inspect') {
        throw new RuntimeException('Unknown isolated operation.');
    }
}
$data['states'] = [];
foreach ($data['assignments'] as $key => $id) {
    $assignment = SyntheticEvidenceReviewerAssignment::findOrFail($id);
    $data['states'][$key] = ['revoked' => $assignment->revoked_at !== null, 'actor' => $assignment->revoked_by, 'reason' => $assignment->revocation_reason, 'audits' => AuditEvent::where('action', 'evidence.reviewer_assignment_revoked')->where('resource_type', SyntheticEvidenceReviewerAssignment::class)->where('resource_id', (string) $id)->count()];
}
file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated revocation fixture completed.\n";
