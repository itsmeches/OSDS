<?php

use App\Models\AuditEvent;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Support\AccountRole;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$action = $argv[1] ?? 'inspect';
$file = storage_path('app/private/evidence-reviewer-creation.json');
if ($action === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $actor = User::factory()->create(['name' => 'Synthetic creation manager', 'email' => 'gint005-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->assignRole(AccountRole::Admin->value);
    $reviewer = User::factory()->create(['name' => 'Synthetic creation reviewer '.$suffix, 'is_synthetic' => true]);
    $reviewer->assignRole(AccountRole::Chedro->value);
    $region = Region::create(['code' => 'G5-'.$suffix, 'name' => 'Isolated creation region '.$suffix]);
    $module = OsdsModule::firstOrFail();
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'reviewer' => $reviewer->id, 'reviewerName' => $reviewer->name,
        'region' => $region->id, 'regionName' => $region->name, 'module' => $module->id, 'moduleName' => $module->name];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint005-')) {
        throw new RuntimeException('Isolated fixture required.');
    }
    if ($action === 'revoke-permission') {
        $remaining = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === 'evidence.reviewer.assignments.manage')->all();
        $actor->syncRoles([]);
        $actor->syncPermissions($remaining);
    } elseif ($action !== 'inspect') {
        throw new RuntimeException('Unknown isolated operation.');
    }
}
$rows = SyntheticEvidenceReviewerAssignment::where('reviewer_id', $data['reviewer'])->get();
$data['assignments'] = $rows->map(fn ($row) => ['id' => $row->id, 'reason' => $row->reason, 'granted_by' => $row->granted_by,
    'audits' => AuditEvent::where('action', 'evidence.reviewer_assignment_granted')->where('resource_type', SyntheticEvidenceReviewerAssignment::class)->where('resource_id', (string) $row->id)->count()])->all();
file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated creation fixture completed.\n";
