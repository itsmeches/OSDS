<?php

use App\Models\Application;
use App\Models\ApplicationSubmission;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$requested = $argv[1] ?? 'inspect';
$snapshotInstitution = null;
if ($requested === 'prepare-snapshots') {
    $argv[1] = 'prepare';
    require __DIR__.'/Prepare-ChedroJourney.php';
    $seed = json_decode(file_get_contents(storage_path('app/private/chedro-journey.json')), true);
    $snapshotInstitution = Application::where('public_id', $seed['applications'][0])->value('institution_id');
} else {
    require __DIR__.'/Bootstrap-FindingAtomicity.php';
}
$file = storage_path('app/private/institution-update.json');
if (in_array($requested, ['prepare', 'prepare-snapshots'])) {
    $suffix = Str::lower(Str::random(8));
    $region = $snapshotInstitution ? Institution::findOrFail($snapshotInstitution)->primaryRegion : Region::create(['code' => 'K1-'.$suffix, 'name' => 'K1 current '.$suffix]);
    $destination = Region::create(['code' => 'K1-B-'.$suffix, 'name' => 'K1 destination '.$suffix]);
    $outside = Region::create(['code' => 'K1-C-'.$suffix, 'name' => 'K1 outside '.$suffix]);
    $institution = $snapshotInstitution ? Institution::findOrFail($snapshotInstitution) : Institution::create(['name' => 'K1 original '.$suffix, 'primary_region_id' => $region->id, 'is_synthetic' => true]);
    $actor = User::factory()->create(['name' => 'Synthetic institution editor', 'email' => 'k1edit-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
    $grants = UserRegionAssignment::withoutEvents(fn () => collect([$region, $destination])->map(fn ($r) => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $r->id, 'starts_at' => now()->subDay()])));
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'institution' => $institution->id, 'originalName' => $institution->name, 'region' => $region->id, 'destination' => $destination->id, 'destinationName' => $destination->name, 'outside' => $outside->id, 'scope' => $grants[0]->id, 'destinationScope' => $grants[1]->id];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'k1edit-')) {
        throw new RuntimeException('Isolated K1 fixture required.');
    }
    if ($requested === 'revoke-permission') {
        $actor->revokePermissionTo('institutions.manage');
    } elseif ($requested === 'blocked') {
        $pid = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
        if (! $pid) {
            throw new RuntimeException('Worker PID required.');
        }
        echo json_encode(['blocked' => DB::selectOne('SELECT cardinality(pg_blocking_pids(?)) > 0 AS blocked', [$pid])->blocked]);
        exit;
    } elseif ($requested !== 'inspect') {
        throw new RuntimeException('Unknown isolated operation.');
    }
}
$row = Institution::findOrFail($data['institution']);
if (! $row->is_synthetic) {
    throw new RuntimeException('Synthetic institution required.');
}
$data['saved'] = $row->only(['id', 'name', 'official_id', 'primary_region_id', 'status', 'organization_type', 'is_synthetic']);
$data['audits'] = AuditEvent::where('resource_type', Institution::class)->where('resource_id', (string) $row->id)->where('action', Institution::class.'.updated')->get(['id', 'actor_id', 'region_id', 'institution_id', 'metadata'])->toArray();
$snapshots = ApplicationSubmission::where('institution_id', $row->id)->orderBy('id')->get()->toArray();
$data['submissionCount'] = count($snapshots);
$data['submissionHash'] = hash('sha256', json_encode($snapshots));
$data['routingRegions'] = array_column($snapshots, 'routing_region_id');
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Isolated K1 fixture completed.\n";
