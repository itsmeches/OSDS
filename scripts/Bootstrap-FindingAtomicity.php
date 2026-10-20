<?php

use Illuminate\Contracts\Console\Kernel;

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
    throw new RuntimeException('Browser database required.');
}
