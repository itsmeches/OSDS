<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\PostgresConnection;

$root = dirname(__DIR__);
$dir = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel';
require $root.'/vendor/autoload.php';
// Reuse fixed guards and read-verification functions, never the preparation CLI.
$source = file_get_contents($root.'/scripts/Prepare-UiUxBrowser.php');
$uses = substr($source, strpos($source, 'use App\\Models'), strpos($source, 'if ($argc') - strpos($source, 'use App\\Models'));
$start = strpos($source, 'function b2Target');
eval($uses.substr($source, $start, strpos($source, '$pdo = null;') - $start));
if (is_file($root.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Cached configuration prohibited');
}
foreach (['DB_URL', 'DATABASE_URL', 'APP_CONFIG_CACHE', 'PGHOST', 'PGPORT', 'PGDATABASE', 'PGSERVICE', 'PGSERVICEFILE'] as $key) {
    if (($v = getenv($key)) !== false && $v !== '') {
        throw new RuntimeException('Unexpected override '.$key);
    }
}
$env = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
if (($env['DB_HOST'] ?? '') !== '127.0.0.1' || (string) ($env['DB_PORT'] ?? '') !== '55432' || ($env['DB_CONNECTION'] ?? '') !== 'pgsql' || ! empty($env['DB_URL'])) {
    throw new RuntimeException('Credential endpoint mismatch');
}
$fixed = ['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8044', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '55432', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'DATABASE_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => PHP_SAPI === 'cli-server' ? 'file' : 'array', 'SESSION_COOKIE' => 'uiux_b2_personnel_session', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync'];
foreach ($fixed as $key => $value) {
    if (str_starts_with($key, 'DB_') && ($old = getenv($key)) !== false && $old !== $value) {
        throw new RuntimeException('Unexpected target '.$key);
    }putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
foreach (['DB_USERNAME', 'DB_PASSWORD'] as $key) {
    $value = $env[$key] ?? '';
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$c = ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => '55432', 'database' => 'osds_ui_browser_test', 'username' => $env['DB_USERNAME'], 'password' => $env['DB_PASSWORD'] ?? '', 'charset' => 'utf8', 'timezone' => 'UTC', 'search_path' => 'public', 'sslmode' => 'prefer'];
b2Target($c);
$pdo = (new PostgresConnector)->connect($c);
b2Identity($pdo);
unset($pdo);
Connection::resolverFor('pgsql', static function ($unused, $database, $prefix, $config) {
    b2Target($config);

    return new PostgresConnection(static function () use ($config) {
        b2Target($config);
        $pdo = (new PostgresConnector)->connect($config);
        b2Identity($pdo);

        return $pdo;
    }, $database, $prefix, $config);
});
foreach (['sqlite', 'mysql', 'mariadb', 'sqlsrv'] as $driver) {
    Connection::resolverFor($driver, static function () {
        throw new RuntimeException('Other database drivers prohibited');
    });
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['session.files' => $dir.'/sessions']);
