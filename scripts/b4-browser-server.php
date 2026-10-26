<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli-server' || (int) ($_SERVER['SERVER_PORT'] ?? 0) !== 8040) {
    http_response_code(503);
    exit('Isolated B4 server required');
}
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8040', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_COOKIE' => 'b4_browser_session', 'DEMO_PDF_NODE' => sys_get_temp_dir().'/osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    http_response_code(503);
    exit('Uncached isolated configuration required');
}
$path = realpath(__DIR__.'/../public'.parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$public = realpath(__DIR__.'/../public');
if ($path !== false && str_starts_with($path, $public.DIRECTORY_SEPARATOR) && is_file($path)) {
    return false;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test' || DB::selectOne('select current_database() as name')->name !== 'osds_ui_browser_test') {
    http_response_code(503);
    exit('Configured and actual isolated B4 database required');
}
$app->handleRequest(Request::capture());
