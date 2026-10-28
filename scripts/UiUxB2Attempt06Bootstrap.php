<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\PostgresConnection;

$root = dirname(__DIR__);
$preparation = defined('UIUX_B206_PREPARATION') && UIUX_B206_PREPARATION;
if ($preparation && PHP_SAPI !== 'cli') {
    throw new RuntimeException('Preparation is CLI inspection only');
}
$dir = $root.($preparation ? '/storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-06-preparation/inspection-01' : '/storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-06');
$pins = json_decode(file_get_contents($root.'/scripts/uiux-b2-attempt-06-pins.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($pins['sources'] as $pin) {
    if (! hash_equals($pin['sha256'], hash_file('sha256', $root.'/'.$pin['path']))) {
        throw new RuntimeException('Attempt 06 source pin changed');
    }
}
// A future authorized launcher must reserve this fixed directory first.
if (! is_dir($dir)) {
    throw new RuntimeException('Reserved reviewer-only evidence directory required');
}
$reservation = json_decode(file_get_contents($dir.'/reserved.json'), true, flags: JSON_THROW_ON_ERROR);
$sessionPath = realpath($dir.'/sessions');
if (($reservation['attempt'] ?? null) !== ($preparation ? 'b2-attempt-06-readonly-preparation' : 'b2-reviewer-only-attempt-06') || $sessionPath === false
    || ! is_writable($sessionPath) || basename($sessionPath) !== 'sessions' || realpath(dirname($sessionPath)) !== realpath($dir)
    || $sessionPath !== realpath($reservation['sessions'] ?? '')) {
    throw new RuntimeException('Reserved writable attempt-local sessions required');
}
define('UIUX_B2_SESSION_DIRECTORY', $sessionPath);
define('UIUX_B2_IDENTITY_LOG', $dir.'/identities.jsonl');
require $root.'/vendor/autoload.php';
// Reuse fixed guards and read-verification functions, never the preparation CLI.
$source = file_get_contents($root.'/scripts/Prepare-UiUxBrowser.php');
$uses = substr($source, strpos($source, 'use App\\Models'), strpos($source, 'if ($argc') - strpos($source, 'use App\\Models'));
$start = strpos($source, 'function b2Target');
$functions = substr($source, $start, strpos($source, '$pdo = null;') - $start);
// Bind the logger locally; Laravel includes must not redirect a mutable global.
if (substr_count($functions, 'global $dir;') !== 1) {
    throw new RuntimeException('Unexpected identity logger source');
}
$functions = str_replace('global $dir;', '$dir = dirname(UIUX_B2_IDENTITY_LOG);', $functions);
eval($uses.$functions);
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
function b206Target(array $config): void
{
    b2Target($config);
    foreach (['connect_via_database', 'connect_via_port', 'server_options'] as $key) {
        if (array_key_exists($key, $config)) {
            throw new RuntimeException('Unsupported connection override');
        }
    }
}
function b206Connect(array $config): PDO
{
    b206Target($config);
    $pdo = (new PostgresConnector)->connect($config);
    if (PHP_SAPI === 'cli') {
        $pdo->exec('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY');
        if ($pdo->query('SHOW default_transaction_read_only')->fetchColumn() !== 'on') {
            throw new RuntimeException('CLI read-only default required');
        }
    }
    b2Identity($pdo);

    return $pdo;
}
$c = ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => '55432', 'database' => 'osds_ui_browser_test', 'username' => $env['DB_USERNAME'], 'password' => $env['DB_PASSWORD'] ?? '', 'charset' => 'utf8', 'timezone' => 'UTC', 'search_path' => 'public', 'sslmode' => 'prefer'];
b2Target($c);
$pdo = b206Connect($c);
unset($pdo);
Connection::resolverFor('pgsql', static function ($unused, $database, $prefix, $config) {
    b206Target($config);

    return new PostgresConnection(static function () use ($config) {
        b206Target($config);

        return b206Connect($config);
    }, $database, $prefix, $config);
});
foreach (['sqlite', 'mysql', 'mariadb', 'sqlsrv'] as $driver) {
    Connection::resolverFor($driver, static function () {
        throw new RuntimeException('Other database drivers prohibited');
    });
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['session.files' => UIUX_B2_SESSION_DIRECTORY]);
