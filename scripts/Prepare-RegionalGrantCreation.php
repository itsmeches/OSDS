<?php

use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Support\AccountRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$action = $argv[1] ?? 'inspect';
$file = storage_path('app/private/regional-grant-creation.json');
if ($action === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $actor = User::factory()->create(['name' => 'Synthetic regional manager', 'email' => 'gint006-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->assignRole(AccountRole::Admin->value);
    // Direct permissions make pivot-revocation concurrency independently testable.
    $permissions = $actor->getAllPermissions()->pluck('name')->all();
    $actor->syncRoles([]);
    $actor->syncPermissions($permissions);
    $target = User::factory()->create(['name' => 'Synthetic delegated account '.$suffix, 'is_synthetic' => true]);
    $target->assignRole(AccountRole::Osds->value);
    $regions = [];
    foreach (['A', 'B', 'C'] as $letter) {
        $regions[] = Region::create(['code' => 'G6-'.$letter.'-'.$suffix, 'name' => 'Synthetic region '.$letter.' '.$suffix]);
    }
    auth()->login($actor);
    $scopes = UserRegionAssignment::withoutEvents(function () use ($actor, $target, $regions) {
        $rows = [];
        foreach ($regions as $region) {
            $rows[] = UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay()]);
        }
        UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $regions[0]->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay()]);

        return $rows;
    });
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'target' => $target->id, 'targetName' => $target->name,
        'region' => $regions[1]->id, 'regionName' => $regions[1]->name, 'otherRegion' => $regions[2]->id, 'otherRegionName' => $regions[2]->name, 'scope' => $scopes[1]->id];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint006-')) {
        throw new RuntimeException('Isolated fixture required.');
    }
    if ($action === 'revoke-permission') {
        $actor->revokePermissionTo('region_scopes.assign');
    } elseif ($action === 'blocked') {
        $pid = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
        if (! $pid) {
            throw new RuntimeException('Worker PID required.');
        }
        echo json_encode(['blocked' => DB::selectOne('SELECT cardinality(pg_blocking_pids(?)) > 0 AS blocked', [$pid])->blocked]);
        exit;
    } elseif ($action !== 'inspect') {
        throw new RuntimeException('Unknown isolated operation.');
    }
}
$data['assignments'] = UserRegionAssignment::where('user_id', $data['target'])->whereIn('region_id', [$data['region'], $data['otherRegion']])->get()
    ->map(fn ($row) => ['id' => $row->id, 'region_id' => $row->region_id, 'granted_by' => $row->granted_by,
        'audits' => AuditEvent::where('action', UserRegionAssignment::class.'.created')->where('resource_type', UserRegionAssignment::class)->where('resource_id', (string) $row->id)->count()])->all();
file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated regional grant fixture completed.\n";
