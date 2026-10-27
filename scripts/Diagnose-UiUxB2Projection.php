<?php

// Single read-only diagnostic; no Laravel bootstrap or preparation operation.
declare(strict_types=1);

use Illuminate\Database\Connectors\PostgresConnector;

if ($argc !== 1) {
    fwrite(STDERR, "No diagnostic arguments are accepted.\n");
    exit(2);
}
$root = dirname(__DIR__);
$dir = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-projection-diagnostic';
if (! mkdir($dir.'/inspection-01', 0700)) {
    throw new RuntimeException('Inspection path exists; no retry permitted.');
}
$run = $dir.'/inspection-01';
require $root.'/vendor/autoload.php';
$result = ['status' => 'BLOCKED', 'database' => 'osds_ui_browser_test', 'connection_opened' => false];
$pdo = null;
try {
    if (is_file($root.'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached configuration prohibited.');
    }
    foreach (['DB_URL', 'DATABASE_URL', 'APP_CONFIG_CACHE', 'PGHOST', 'PGPORT', 'PGDATABASE', 'PGSERVICE', 'PGSERVICEFILE'] as $key) {
        if (($v = getenv($key)) !== false && $v !== '') {
            throw new RuntimeException('Unexpected override '.$key);
        }
    }
    foreach (['DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '55432', 'DB_DATABASE' => 'osds_ui_browser_test'] as $key => $expected) {
        if (($v = getenv($key)) !== false && $v !== $expected) {
            throw new RuntimeException('Unexpected target '.$key);
        }
    }
    $env = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    if (($env['DB_CONNECTION'] ?? '') !== 'pgsql' || ($env['DB_HOST'] ?? '') !== '127.0.0.1' || (string) ($env['DB_PORT'] ?? '') !== '55432' || ! empty($env['DB_URL'])) {
        throw new RuntimeException('Credential endpoint mismatch');
    }
    $config = ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => '55432', 'database' => 'osds_ui_browser_test', 'username' => $env['DB_USERNAME'], 'password' => $env['DB_PASSWORD'] ?? '', 'charset' => 'utf8', 'timezone' => 'UTC', 'search_path' => 'public', 'sslmode' => 'prefer'];
    $result['configured'] = array_intersect_key($config, array_flip(['driver', 'host', 'port', 'database', 'timezone']));
    $pdo = (new PostgresConnector)->connect($config);
    $result['connection_opened'] = true;
    $pdo->exec('SET default_transaction_read_only=on');
    $pdo->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $pdo->exec("SET LOCAL statement_timeout='15s'");
    $pdo->exec("SET LOCAL lock_timeout='2s'");
    $identity = $pdo->query("SELECT current_database() AS database,host(inet_server_addr()) AS host,inet_server_port() AS port,pg_backend_pid() AS pid,current_setting('transaction_read_only') AS read_only,current_setting('default_transaction_read_only') AS default_read_only,current_setting('TimeZone') AS timezone")->fetch(PDO::FETCH_ASSOC);
    $identity['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $result['identity'] = $identity;
    file_put_contents($run.'/identity.json', json_encode($identity, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    foreach (['driver' => 'pgsql', 'database' => 'osds_ui_browser_test', 'host' => '127.0.0.1', 'port' => '55432', 'read_only' => 'on', 'default_read_only' => 'on'] as $field => $expected) {
        if ((string) $identity[$field] !== $expected) {
            throw new RuntimeException('Identity mismatch: '.$field);
        }
    }
    $helper = file_get_contents($root.'/scripts/Run-UiUxAcceptance.php');
    $prefix = substr($helper, 0, strpos($helper, '$root = dirname(__DIR__);'));
    eval(substr($prefix, strpos($prefix, 'function uiuxCompatibilityProjections')));
    $historicalPath = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json';
    $historical = json_decode(file_get_contents($historicalPath), true, flags: JSON_THROW_ON_ERROR);
    $result['source_hashes'] = ['historical_log' => hash_file('sha256', $historicalPath), 'b0_helper' => hash('sha256', $helper), 'b2_helper' => hash_file('sha256', $root.'/scripts/Prepare-UiUxBrowser.php')];
    $result['query_selection_identical'] = $historical['query_projections'] === uiuxCompatibilityProjections();
    file_put_contents($run.'/queries.json', json_encode(uiuxCompatibilityProjections(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    $result['current_fetch_mode'] = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
    $observed = [];
    try {
        uiuxCompatibility($pdo, $observed);
    } finally {
        file_put_contents($run.'/observed-projections.json', json_encode($observed, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    $before = $historical['compatibility']['data'];
    $now = $observed['data'];
    $result['original_b2_equality'] = $now == $before;
    $result['php_strict_equality'] = $now === $before;
    $result['groups'] = [];
    $result['field_differences'] = [];
    $canonicalBefore = [];
    $canonicalNow = [];
    foreach (array_unique([...array_keys($before), ...array_keys($now)]) as $group) {
        $left = $before[$group] ?? [];
        $right = $now[$group] ?? [];
        $key = static fn ($row) => isset($row['id']) ? (string) $row['id'] : (isset($row['approval_id']) ? (string) $row['approval_id'] : $row['documentary_requirement_id'].':'.$row['provider_id']);
        $oldIds = array_map($key, $left);
        $newIds = array_map($key, $right);
        $result['groups'][$group] = ['historical_ids' => $oldIds, 'current_ids' => $newIds, 'missing' => array_values(array_diff($oldIds, $newIds)), 'added' => array_values(array_diff($newIds, $oldIds)), 'order_identical' => $oldIds === $newIds];
        foreach ($right as $n => $row) {
            $id = $key($row);
            $index = array_search($id, $oldIds, true);
            $oldRow = $index === false ? [] : $left[$index];
            foreach (array_unique([...array_keys($oldRow), ...array_keys($row)]) as $field) {
                if (! array_key_exists($field, $oldRow) || ! array_key_exists($field, $row) || $oldRow[$field] !== $row[$field]) {
                    $result['field_differences'][] = ['group' => $group, 'id' => $id, 'field' => $field, 'historical_present' => array_key_exists($field, $oldRow), 'current_present' => array_key_exists($field, $row), 'historical' => $oldRow[$field] ?? null, 'current' => $row[$field] ?? null, 'class' => is_int($field) ? 'duplicate numeric PDO column' : 'named-field difference'];
                }
            }
        }
        $normalize = static function ($rows) {
            foreach ($rows as &$row) {
                foreach (array_keys($row) as $field) {
                    if (is_int($field)) {
                        unset($row[$field]);
                    }
                }
                foreach (['effective_from', 'effective_until'] as $field) {
                    if (isset($row[$field])) {
                        $row[$field] = (new DateTimeImmutable($row[$field]))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
                    }
                }
            }

            return $rows;
        };
        $canonicalBefore[$group] = $normalize($left);
        $canonicalNow[$group] = $normalize($right);
    }
    $result['canonical_equality'] = $canonicalBefore === $canonicalNow;
    file_put_contents($run.'/canonical-projections.json', json_encode(['historical' => $canonicalBefore, 'current' => $canonicalNow], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    $result['closing_read_only'] = $pdo->query('SHOW transaction_read_only')->fetchColumn();
    $pdo->rollBack();
    $result['transaction_closed'] = 'ROLLBACK';
    $result['status'] = 'PASS';
} catch (Throwable $e) {
    if ($pdo?->inTransaction()) {
        $pdo->rollBack();
        $result['transaction_closed'] = 'ROLLBACK';
    }
    $result['error_class'] = $e::class;
    $result['error'] = $e->getMessage();
}
file_put_contents($run.'/result.json', json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['status' => $result['status'], 'original_b2_equality' => $result['original_b2_equality'] ?? null, 'canonical_equality' => $result['canonical_equality'] ?? null, 'error' => $result['error'] ?? null]).PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);
