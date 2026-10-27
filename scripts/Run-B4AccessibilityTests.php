<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
foreach (['APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'osds_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Uncached isolated configuration required');
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || DB::connection()->getDatabaseName() !== 'osds_test' || DB::selectOne('select current_database() as name')->name !== 'osds_test') {
    throw new RuntimeException('Approved osds_test identity required before regression');
}
echo "Verified PostgreSQL database: osds_test\n";
$process = new Process([PHP_BINARY, '-d', 'memory_limit=512M', base_path('vendor/phpunit/phpunit/phpunit'), ...(($argv[1] ?? '') === 'focused' ? ['--filter', 'FormMakerTest|QuestionnaireConfigurationTest|ApplicationDecisionFoundationTest'] : [])], base_path(), timeout: 7200);
$code = $process->run(function ($type, $buffer) {
    echo $buffer;
});
exit($code);
