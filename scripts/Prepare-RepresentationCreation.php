<?php

use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Support\AccountRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$action = $argv[1] ?? 'inspect';
$file = storage_path('app/private/representation-creation.json');
if ($action === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $actor = User::factory()->create(['name' => 'Synthetic representation manager', 'email' => 'gint007-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $actor->assignRole(AccountRole::Chedro->value);
    $permissions = $actor->getAllPermissions()->pluck('name')->all();
    $actor->syncRoles([]);
    $actor->syncPermissions($permissions);
    $target = User::factory()->create(['name' => 'Synthetic SCHOOL representative '.$suffix, 'is_synthetic' => true]);
    $target->assignRole(AccountRole::School->value);
    $region = Region::create(['code' => 'G7-'.$suffix, 'name' => 'Synthetic region '.$suffix]);
    $outside = Region::create(['code' => 'G7-O-'.$suffix, 'name' => 'Outside fixture region '.$suffix]);
    $institution = Institution::create(['name' => 'Synthetic institution A '.$suffix, 'primary_region_id' => $region->id, 'is_synthetic' => true]);
    $other = Institution::create(['name' => 'Synthetic institution B '.$suffix, 'primary_region_id' => $region->id, 'is_synthetic' => true]);
    $scope = UserRegionAssignment::withoutEvents(function () use ($actor, $target, $region) {
        UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $region->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay()]);

        return UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay()]);
    });
    $data = ['actor' => $actor->id, 'email' => $actor->email, 'target' => $target->id, 'targetName' => $target->name,
        'institution' => $institution->id, 'institutionName' => $institution->name, 'otherInstitution' => $other->id, 'otherInstitutionName' => $other->name,
        'scope' => $scope->id, 'outsideRegion' => $outside->id];
} else {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($data['actor']);
    if (! $actor->is_synthetic || ! str_starts_with($actor->email, 'gint007-')) {
        throw new RuntimeException('Isolated fixture required.');
    }
    if ($action === 'revoke-permission') {
        $actor->revokePermissionTo('institution_representatives.assign');
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
$data['assignments'] = InstitutionRepresentative::where('user_id', $data['target'])->whereIn('institution_id', [$data['institution'], $data['otherInstitution']])->get()
    ->map(fn ($row) => ['id' => $row->id, 'institution_id' => $row->institution_id, 'granted_by' => $row->granted_by, 'verified' => $row->verified_at !== null,
        'audits' => AuditEvent::where('action', InstitutionRepresentative::class.'.created')->where('resource_type', InstitutionRepresentative::class)->where('resource_id', (string) $row->id)->count()])->all();
file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated representation fixture completed.\n";
