<?php

use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Support\AccountRole;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Uncached isolated configuration required.');
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test') {
    throw new RuntimeException('Refusing non-browser database.');
}
$file = storage_path('app/private/account-security.json');
$action = $argv[1] ?? 'prepare';
if ($action === 'prepare') {
    $suffix = Str::lower(Str::random(8));
    $region = Region::create(['code' => 'S7-'.$suffix, 'name' => 'SEC007 disposable region']);
    $fixture = [];
    foreach (['actor' => AccountRole::Admin, 'target' => AccountRole::Osds, 'restricted' => AccountRole::SuperUser] as $key => $role) {
        $u = User::factory()->create(['name' => 'Synthetic '.$key, 'email' => 'sec007-'.$key.'-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true, 'remember_token' => 'SEC007-remember-marker']);
        $u->assignRole($role->value);
        UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $u->id, 'region_id' => $region->id, 'starts_at' => now()->subMinute()]));
        $fixture[$key] = ['id' => $u->id, 'email' => $u->email];
    }
    $fixture['region'] = $region->id;
} else {
    $fixture = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $actor = User::findOrFail($fixture['actor']['id']);
    if (! str_starts_with($actor->email, 'sec007-') || ! $actor->is_synthetic) {
        throw new RuntimeException('Unrelated fixture.');
    }
    if ($action === 'revoke-permission') {
        $remaining = $actor->getAllPermissions()->pluck('name')->reject(fn ($name) => $name === 'users.manage')->all();
        $actor->syncRoles([]);
        $actor->syncPermissions($remaining);
    } elseif ($action === 'revoke-scope') {
        $actor->regionAssignments()->update(['revoked_at' => now()]);
    } elseif ($action !== 'inspect') {
        throw new RuntimeException('Unknown operation.');
    }
}
foreach (['target', 'restricted'] as $key) {
    $u = User::findOrFail($fixture[$key]['id']);
    $fixture[$key]['name'] = $u->name;
    $fixture[$key]['saved_email'] = $u->email;
    $fixture[$key]['changed_password'] = Hash::check('SEC007-browser-password!', $u->password);
    $fixture[$key]['audit_count'] = AuditEvent::where('resource_type', User::class)->where('resource_id', (string) $u->id)->count();
}
file_put_contents($file, json_encode($fixture, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated account fixture completed.\n";
