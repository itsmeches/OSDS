<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Boost\Mcp\Tools\SearchDocs;
use Laravel\Mcp\Server\McpServiceProvider;
use Symfony\Component\Console\Input\ArgvInput;

$root = dirname(__DIR__);
chdir($root);
$allowed = [ApplicationInfo::class, SearchDocs::class];
$toolMode = ($argv[1] ?? null) === 'tool';
if (($argc !== 1 && ! $toolMode) || ($toolMode && ($argc !== 4 || ! in_array($argv[2], $allowed, true)))) {
    throw new RuntimeException('Only the MCP server and its two fixed informational tools are allowed.');
}
if (is_file($root.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Cached application configuration is prohibited for Boost.');
}
foreach (['DB_URL', 'DATABASE_URL', 'APP_CONFIG_CACHE', 'PGSERVICE', 'PGSERVICEFILE'] as $key) {
    if (($value = getenv($key)) !== false && $value !== '') {
        throw new RuntimeException('Unexpected connection/configuration override.');
    }
}
// No credentials are loaded. PDO access is denied, even to the named test target.
$fixed = ['APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '55432', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_USERNAME' => 'boost_database_disabled', 'DB_PASSWORD' => '', 'DB_URL' => '', 'DATABASE_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BOOST_ENABLED' => 'true', 'BOOST_BROWSER_LOGS_WATCHER' => 'false', 'BOOST_RULES_ENABLED' => 'false', 'LOG_CHANNEL' => 'stderr'];
foreach ($fixed as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require $root.'/vendor/autoload.php';
Connection::resolverFor('pgsql', static function ($unused, $database, $prefix, $config) {
    if (($config['database'] ?? '') !== 'osds_ui_browser_test' || ($config['host'] ?? '') !== '127.0.0.1' || (string) ($config['port'] ?? '') !== '55432' || ! empty($config['url']) || isset($config['read']) || isset($config['write'])) {
        throw new RuntimeException('Unexpected Boost database configuration.');
    }

    return new PostgresConnection(static function () {
        throw new RuntimeException('All database connections are disabled in Boost pending separate authorization.');
    }, $database, $prefix, $config);
});
foreach (['sqlite', 'mysql', 'mariadb', 'sqlsrv'] as $driver) {
    Connection::resolverFor($driver, static function () {
        throw new RuntimeException('Database driver disabled in Boost.');
    });
}
$app = require $root.'/bootstrap/app.php';
$app->afterBootstrapping(LoadConfiguration::class, static function ($app) use ($allowed, $root) {
    $all = array_map(static fn ($file) => 'Laravel\\Boost\\Mcp\\Tools\\'.basename($file, '.php'), glob($root.'/vendor/laravel/boost/src/Mcp/Tools/*.php'));
    $app['config']->set('boost.enabled', true);
    $app['config']->set('boost.browser_logs_watcher', false);
    $app['config']->set('boost.rules.enabled', false);
    $app['config']->set('boost.mcp.tools.exclude', array_values(array_diff($all, $allowed)));
    $app['config']->set('boost.mcp.tools.include', []);
});
$app->make(Kernel::class)->bootstrap();
// Explicit registration avoids running Composer's application-wide discovery scripts.
$app->register(McpServiceProvider::class);
$app->register(BoostServiceProvider::class);
$app->bind(ToolExecutor::class, static fn () => new class extends ToolExecutor
{
    protected function buildCommand(string $toolClass, array $arguments): array
    {
        return [PHP_BINARY, __FILE__, 'tool', $toolClass, base64_encode(json_encode($arguments, JSON_THROW_ON_ERROR))];
    }
});
$input = $toolMode
    ? [__FILE__, 'boost:execute-tool', $argv[2], $argv[3]]
    : [__FILE__, 'boost:mcp'];
exit($app->handleCommand(new ArgvInput($input)));
