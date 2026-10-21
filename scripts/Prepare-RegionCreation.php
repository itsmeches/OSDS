<?php

use App\Models\AuditEvent;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$mode = $argv[1] ?? 'inspect';
$file = storage_path('app/private/region-creation.json');
if (in_array($mode, ['prepare', 'prepare-role', 'prepare-browser'])) {
    $suffix = Str::lower(Str::random(8));
    $actors = collect(['a', 'b'])->map(function ($key) use ($suffix, $mode) {
        $actor = User::factory()->create(['name' => 'Synthetic region creator '.$key, 'email' => 'k5create-'.$key.'-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'regions.view']);
        if ($mode === 'prepare-role' && $key === 'b') {
            $actor->assignRole('Admin');
        } else {
            $actor->givePermissionTo('regions.manage');
        }

        return $actor;
    });
    if ($mode === 'prepare-browser') {
        // Saved-list inspection is independently scoped. This pre-existing
        // nationwide fixture authorizes reading, not creation; feature tests
        // and workers deliberately create without any scope grants.
        NationwideAccessGrant::create(['user_id' => $actors[0]->id, 'granted_by' => $actors[1]->id, 'starts_at' => now()->subDay(), 'reason' => 'Disposable K5 saved-list inspection']);
    }
    $data = ['actor' => $actors[0]->id, 'contender' => $actors[1]->id, 'email' => $actors[0]->email, 'code' => 'K5-'.$suffix, 'name' => 'Disposable K5 '.$suffix, 'mode' => $mode];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    foreach ([$data['actor'], $data['contender']] as $id) {
        $actor = User::findOrFail($id);
        if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'k5create-')) {
            throw new RuntimeException('Isolated K5 fixture required.');
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
$data['regions'] = Region::where('code', 'like', $data['code'].'%')->orderBy('id')->get()->map(fn ($row) => $row->only(['id', 'public_id', 'code', 'name', 'is_active']) + ['audits' => AuditEvent::where('action', Region::class.'.created')->where('resource_type', Region::class)->where('resource_id', (string) $row->id)->get(['actor_id', 'region_id', 'institution_id', 'metadata'])->toArray()])->toArray();
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($data)."\n";
