<?php

// Verification-only B1 bootstrap. Never loaded by application entry points.
declare(strict_types=1);
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\PostgresConnection;

require_once dirname(__DIR__).'/vendor/autoload.php';

function uiuxB1Target(array $config): void
{
    if (($config['driver'] ?? '') !== 'pgsql' || ($config['host'] ?? '') !== '127.0.0.1'
        || (string) ($config['port'] ?? '') !== '55432' || ($config['database'] ?? '') !== 'osds_test'
        || ! empty($config['url']) || isset($config['read']) || isset($config['write'])
        || isset($config['direct']) || ! empty($config['options'])) {
        throw new RuntimeException('B1 rejected connection configuration before PDO.');
    }
    if (! in_array($config['name'] ?? 'pgsql', ['pgsql', 'sec006_utc', 'sec006_legacy'], true)) {
        throw new RuntimeException('B1 rejected unexpected connection alias.');
    }
}

function uiuxB1Identity(PDO $pdo): void
{
    $row = $pdo->query('SELECT current_database() AS database, host(inet_server_addr()) AS host, inet_server_port() AS port, pg_backend_pid() AS pid')->fetch(PDO::FETCH_ASSOC);
    $pass = $row['database'] === 'osds_test' && $row['host'] === '127.0.0.1' && (string) $row['port'] === '55432';
    file_put_contents(dirname(__DIR__).'/storage/app/private/uiux-consolidated-acceptance/gate-b1/identities.jsonl', json_encode(['observed' => $row, 'pass' => $pass, 'time' => gmdate('c')]).PHP_EOL, FILE_APPEND | LOCK_EX);
    if (! $pass) {
        throw new RuntimeException('B1 actual PostgreSQL identity mismatch; execution stopped.');
    }
}

if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
    throw new RuntimeException('B1 refuses cached application configuration.');
}
foreach (['APP_CONFIG_CACHE', 'PGSERVICE', 'PGSERVICEFILE', 'PGHOST', 'PGPORT', 'PGDATABASE', 'DATABASE_URL'] as $override) {
    if (($value = getenv($override)) !== false && $value !== '') {
        throw new RuntimeException('B1 rejects ambient connection/cache override '.$override.'.');
    }
}

$testing = Dotenv\Dotenv::parse(file_get_contents(dirname(__DIR__).'/.env.testing'));
uiuxB1Target(['driver' => $testing['DB_CONNECTION'] ?? '', 'host' => $testing['DB_HOST'] ?? '', 'port' => $testing['DB_PORT'] ?? '', 'database' => $testing['DB_DATABASE'] ?? '', 'url' => $testing['DB_URL'] ?? '']);
$fixed = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '55432', 'DB_DATABASE' => 'osds_test', 'DB_URL' => '', 'DATABASE_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync'];
foreach ($fixed as $key => $value) {
    $ambient = getenv($key);
    if (in_array($key, ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_URL', 'DATABASE_URL'], true) && $ambient !== false && $ambient !== $value) {
        throw new RuntimeException('B1 rejected unexpected ambient '.$key.'.');
    }
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
foreach (['DB_USERNAME', 'DB_PASSWORD'] as $key) {
    $value = $testing[$key] ?? '';
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}

Connection::resolverFor('pgsql', static function ($unused, $database, $prefix, $config) {
    uiuxB1Target($config);
    $connect = static function () use ($config) {
        uiuxB1Target($config);
        $pdo = (new PostgresConnector)->connect($config);
        uiuxB1Identity($pdo);

        return $pdo;
    };

    return new PostgresConnection($connect, $database, $prefix, $config);
});
foreach (['sqlite', 'mysql', 'mariadb', 'sqlsrv'] as $driver) {
    Connection::resolverFor($driver, static function () {
        throw new RuntimeException('B1 rejects non-PostgreSQL connection.');
    });
}

// Establish actual identity before Laravel can bootstrap or execute migrations.
$preflight = (new PostgresConnector)->connect([
    'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => '55432', 'database' => 'osds_test',
    'username' => $testing['DB_USERNAME'], 'password' => $testing['DB_PASSWORD'] ?? '',
    'charset' => 'utf8', 'timezone' => 'UTC', 'search_path' => 'public', 'sslmode' => 'prefer',
]);
uiuxB1Identity($preflight);
unset($preflight, $testing);
