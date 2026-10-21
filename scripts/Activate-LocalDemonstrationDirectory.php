<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'pgsql' || DB::connection()->getDatabaseName() !== 'osds_dev') {
    throw new RuntimeException('Local osds_dev required for this additive feature activation.');
}
$migration = '2026_10_21_600000_create_demo_office_directory_revisions';
$pending = ! DB::table('migrations')->where('migration', $migration)->exists();
if (($argv[1] ?? '') === '--apply' && $pending) {
    if (Artisan::call('migrate', ['--path' => 'database/migrations/'.$migration.'.php', '--force' => true]) !== 0) {
        throw new RuntimeException('Directory migration failed.');
    }
    $pending = false;
}
echo json_encode(['database' => 'osds_dev', 'migration_pending' => $pending, 'personnel_imported' => false, 'existing_data_reset' => false], JSON_THROW_ON_ERROR);
