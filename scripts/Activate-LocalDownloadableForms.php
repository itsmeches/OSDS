<?php

use App\Services\Auditing\AuditRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local') || config('database.default') !== 'pgsql'
    || DB::connection()->getDatabaseName() !== 'osds_dev') {
    throw new RuntimeException('This explicitly authorized local activation requires osds_dev in local environment.');
}

$role = Role::where(['name' => 'OSDS', 'guard_name' => 'web'])->firstOrFail();
$readerRole = Role::where(['name' => 'CHEDRO', 'guard_name' => 'web'])->firstOrFail();
$migration = '2026_10_21_500000_create_downloadable_forms';
$pending = ! DB::table('migrations')->where('migration', $migration)->exists();
$existingPermission = Permission::where(['name' => 'forms.downloads.manage', 'guard_name' => 'web'])->first();
$viewPermission = Permission::where(['name' => 'forms.downloads.view', 'guard_name' => 'web'])->first();
$preview = [
    'database' => 'osds_dev', 'migration_pending' => $pending,
    'forms_table_exists' => Schema::hasTable('downloadable_forms'),
    'osds_permission_present' => $existingPermission ? $role->hasPermissionTo($existingPermission) : false,
    'chedro_read_permission_present' => $viewPermission ? $readerRole->hasPermissionTo($viewPermission) : false,
];
if (($argv[1] ?? '') !== '--apply') {
    echo json_encode($preview, JSON_THROW_ON_ERROR);
    exit;
}
if ($pending) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/'.$migration.'.php', '--force' => true]) !== 0) {
        throw new RuntimeException('Targeted migration did not complete.');
    }
}
DB::transaction(function () use ($role, $readerRole): void {
    foreach ([[$role, 'forms.downloads.manage'], [$readerRole, 'forms.downloads.view']] as [$target, $name]) {
        $permission = Permission::findOrCreate($name, 'web');
        if (! $target->hasPermissionTo($permission)) {
            $target->givePermissionTo($permission);
            app(AuditRecorder::class)->record('downloadable_forms.local_access_enabled', 'success', resource: $target,
                metadata: ['permission' => $name, 'context' => 'User-authorized additive local forms access; no role synchronization']);
        }
    }
});
app(PermissionRegistrar::class)->forgetCachedPermissions();
echo json_encode(['database' => 'osds_dev', 'enabled' => true, 'existing_data_reset' => false], JSON_THROW_ON_ERROR);
