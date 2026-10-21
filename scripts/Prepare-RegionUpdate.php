<?php

use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/region-update.json');
if ($mode === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $region = Region::create(['code' => 'K4-'.$suffix, 'name' => 'Disposable K4 '.$suffix, 'is_active' => true]);
    $outside = Region::create(['code' => 'K4-O-'.$suffix, 'name' => 'Disposable K4 outside '.$suffix, 'is_active' => true]);
    $actors = collect(['a', 'b'])->map(function ($key) use ($suffix) {
        $actor = User::factory()->create(['name' => 'Synthetic region editor '.$key, 'email' => 'k4edit-'.$key.'-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view', 'regions.manage']);

        return $actor;
    });
    $grants = UserRegionAssignment::withoutEvents(fn () => $actors->map(fn ($actor) => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()])));
    $data = ['actor' => $actors[0]->id, 'email' => $actors[0]->email, 'contender' => $actors[1]->id, 'scope' => $grants[1]->id, 'region' => $region->id, 'outside' => $outside->id, 'outsideCode' => $outside->code];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    foreach ([$data['actor'], $data['contender']] as $id) {
        $actor = User::findOrFail($id);
        if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'k4edit-')) {
            throw new RuntimeException('Isolated K4 fixture required.');
        }
    }
    if ($mode === 'revoke-permission') {
        User::findOrFail($data['actor'])->revokePermissionTo('regions.manage');
    } elseif ($mode === 'blocked') {
        $pid = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
        if (! $pid) {
            throw new RuntimeException('Worker PID required.');
        }
        echo json_encode(['blocked' => DB::selectOne('SELECT cardinality(pg_blocking_pids(?)) > 0 AS blocked', [$pid])->blocked]);
        exit;
    } elseif ($mode !== 'inspect') {
        throw new RuntimeException('Unknown fixture operation.');
    }
}
$row = Region::findOrFail($data['region']);
if (! str_starts_with($row->code, 'K4-')) {
    throw new RuntimeException('Disposable Region required.');
}
$data['saved'] = $row->only(['id', 'public_id', 'code', 'name', 'is_active']);
$data['audits'] = AuditEvent::where('resource_type', Region::class)->where('resource_id', (string) $row->id)->where('action', Region::class.'.updated')->orderBy('id')->get(['id', 'actor_id', 'region_id', 'institution_id', 'metadata'])->toArray();
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($data)."\n";
