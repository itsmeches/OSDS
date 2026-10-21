<?php

use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/institution-creation.json');
if ($mode === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $region = Region::create(['code' => 'K3-'.$suffix, 'name' => 'K3 disposable region '.$suffix]);
    $actor = User::factory()->create(['name' => 'Synthetic institution creator', 'email' => 'k3create-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->givePermissionTo(['platform.admin.access', 'institutions.view', 'institutions.manage']);
    $grant = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'region' => $region->id, 'regionName' => $region->name, 'scope' => $grant->id, 'name' => 'K3 disposable '.$suffix, 'official' => 'K3-'.$suffix];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'k3create-')) {
        throw new RuntimeException('Isolated K3 fixture required.');
    }
    if ($mode === 'revoke-permission') {
        $actor->revokePermissionTo('institutions.manage');
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
// The generic Create form preserves is_synthetic=false; identify only this
// fictional fixture by its unique official-ID prefix and isolated database.
$data['institutions'] = Institution::where('official_id', 'like', $data['official'].'%')->orderBy('id')->get()->map(function ($row) {
    return $row->only(['id', 'name', 'official_id', 'primary_region_id', 'status', 'organization_type', 'is_synthetic']) + ['audits' => AuditEvent::where('action', Institution::class.'.created')->where('resource_type', Institution::class)->where('resource_id', (string) $row->id)->get(['actor_id', 'region_id', 'institution_id', 'outcome'])->toArray()];
})->toArray();
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($data)."\n";
