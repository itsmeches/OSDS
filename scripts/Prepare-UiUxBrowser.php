<?php

// Gate B2 foundations only. No application/case/document preparation modes.
declare(strict_types=1);

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Support\FoundationPermission;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;

if ($argc !== 2 || ! in_array($argv[1], ['inspect', 'prepare'], true)) {
    fwrite(STDERR, "Only fixed inspect or prepare operations are accepted.\n");
    exit(2);
}
$root = dirname(__DIR__);
$mode = $argv[1];
$dir = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-resumption';
if ($mode === 'prepare') {
    $dir = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01';
    if (! mkdir($dir, 0700)) {
        throw new RuntimeException('Fixed foundation attempt already exists; no retry permitted.');
    }
    file_put_contents($dir.'/started.json', json_encode(['attempt' => 'b2-foundation-attempt-01', 'started' => gmdate('c')], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
if ($mode === 'inspect') {
    $dir = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-corrected-preflight/inspection-01';
    if (! mkdir($dir, 0700)) {
        throw new RuntimeException('Fixed corrected inspection already exists; no retry permitted.');
    }
}
require $root.'/vendor/autoload.php';

function b2Target(array $c): void
{
    if (($c['driver'] ?? '') !== 'pgsql' || ($c['host'] ?? '') !== '127.0.0.1' || (string) ($c['port'] ?? '') !== '55432'
        || ($c['database'] ?? '') !== 'osds_ui_browser_test' || ! empty($c['url']) || ! empty($c['options'])
        || isset($c['read']) || isset($c['write']) || isset($c['direct']) || ($c['name'] ?? 'pgsql') !== 'pgsql') {
        throw new RuntimeException('B2 connection rejected before PDO.');
    }
}
function b2Identity(PDO $pdo): array
{
    global $dir;
    $r = $pdo->query('SELECT current_database() AS database,host(inet_server_addr()) AS host,inet_server_port() AS port,pg_backend_pid() AS pid')->fetch(PDO::FETCH_ASSOC);
    $pass = $r['database'] === 'osds_ui_browser_test' && $r['host'] === '127.0.0.1' && (string) $r['port'] === '55432';
    file_put_contents($dir.'/identities.jsonl', json_encode(['observed' => $r, 'pass' => $pass, 'time' => gmdate('c')]).PHP_EOL, FILE_APPEND | LOCK_EX);
    if (! $pass) {
        throw new RuntimeException('B2 actual database identity failed.');
    }

    return $r;
}
function b2Manifest(): array
{
    $p = ['platform.admin.access'];
    $i = [...$p, 'applications.intake.view', 'applications.intake.answers.view', 'applications.screening.history.view'];
    $e = [...$i, 'applications.screening.start', 'applications.corrections.request', 'applications.evaluation.record', 'applications.intake.evidence.open'];
    $c = [...$i, 'applications.recommendations.record'];
    $d = [...$i, 'applications.decisions.record'];
    $a = ['applicant.portal.access', 'applications.draft.create', 'applications.draft.view', 'applications.draft.update', 'evidence.references.manage', 'evidence.references.open.own', 'evidence.review.copy.create', 'applications.submit', 'applications.track.own', 'applications.corrections.view.own', 'applications.resubmit', 'issuance.requests.submit'];
    $q = ['questionnaires.view', 'questionnaires.draft.manage', 'questionnaires.review.submit', 'questionnaires.publication.view', 'questionnaires.preview.sandbox'];
    $g = ['documents.view', 'documents.generate', 'documents.download', 'documents.recover'];
    $m = [
        'osds' => [...$i, ...$q, ...$g, 'forms.downloads.manage', 'forms.downloads.view', 'issuance.definitions.manage', 'documents.personnel.manage', 'users.view', 'institutions.view', 'regions.view', 'applicant.sandbox.publish'],
        'config-reviewer' => [...$p, 'questionnaires.view', 'questionnaires.review', 'questionnaires.publication.view', 'issuance.definitions.review', 'issuance.definitions.publish', 'documents.view', 'documents.personnel.review', 'documents.personnel.publish'],
        'evaluator' => $e, 'chief' => $c, 'director' => $d,
        'evidence-reviewer' => [...$p, 'evidence.reviews.view', 'evidence.reviews.open', 'evidence.review.findings.create', 'evidence.review.history.view'],
        'read-only' => $i, 'questionnaire-view' => [...$p, 'questionnaires.view'], 'combined' => [...$e, ...$c, ...$d],
        'panel-only' => $p, 'no-panel' => ['applications.intake.view'], 'inactive' => $e, 'revocation' => $e,
        'wrong-region' => [...$e, ...$c, ...$d, ...$g], 'cor-only' => [...$e, ...$c, ...$d, ...$g], 'acsa-only' => [...$e, ...$c, ...$d, ...$g],
        'applicant' => [...$a, 'forms.downloads.view'], 'unrelated' => $a,
    ];
    foreach ($m as &$permissions) {
        $permissions = array_values(array_unique($permissions));
        sort($permissions);
    }

    return $m;
}
function b2Namespace(PDO $pdo): void
{
    foreach (['users' => ['name', 'email'], 'regions' => ['code', 'name'], 'institutions' => ['name'], 'form_definitions' => ['name'], 'issuance_definitions' => ['title']] as $table => $columns) {
        foreach ($columns as $column) {
            $s = $pdo->prepare("SELECT count(*) FROM $table WHERE $column LIKE ?");
            $s->execute(['%uiux_20261027_a01%']);
            if ((int) $s->fetchColumn() !== 0) {
                throw new RuntimeException('Namespace collision; inspect without retrying.');
            }
        }
    }
    $s = $pdo->query("SELECT count(*) FROM users WHERE email LIKE '%@uiux-20261027-a01.invalid'");
    if ((int) $s->fetchColumn()) {
        throw new RuntimeException('Namespace email collision.');
    }
}
function b2Protected(PDO $pdo): array
{
    $scope = [
        'publication_manifests' => 'id IN (128,198,199)',
        'publication_manifest_retirements' => 'publication_manifest_id IN (128,198,199)',
        'issuance_definitions' => 'id IN (54,55)',
        'demonstration_document_versions' => "id IN (43,45) OR kind='personnel'",
        'demo_office_directory_revisions' => 'true',
        'form_versions' => 'id IN (466,591,592,593,594)',
        'requirement_set_versions' => 'id IN (466,591,592,593,594)',
        'form_sections' => 'form_version_id IN (466,591,592,593,594)',
        'form_questions' => 'form_version_id IN (466,591,592,593,594)',
        'form_question_options' => 'form_question_id IN (SELECT id FROM form_questions WHERE form_version_id IN (466,591,592,593,594))',
        'documentary_requirements' => 'requirement_set_version_id IN (466,591,592,593,594)',
        'configuration_review_approvals' => 'id IN (137,264,265,266,267)',
    ];
    $out = [];
    foreach ($scope as $table => $where) {
        $out[$table] = $pdo->query("SELECT id,md5(to_jsonb(t)::text) AS digest FROM $table t WHERE $where ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    return $out;
}

function b2VerifyFoundation($db, array $ledger, array $manifest): array
{
    $ids = array_column($ledger['accounts'], 'id');
    $counts = [];
    foreach ($manifest as $label => $names) {
        $u = User::findOrFail($ledger['accounts'][$label]['id']);
        if ($u->name !== 'uiux_20261027_a01 '.$label || $u->email !== $label.'@uiux-20261027-a01.invalid'
            || ! $u->is_synthetic || $u->status !== ($label === 'inactive' ? 'inactive' : 'active')
            || $u->getDirectPermissions()->pluck('name')->sort()->values()->all() !== $names || $u->roles()->exists()) {
            throw new RuntimeException('Actual account/permission mismatch: '.$label);
        }
    }
    $counts['accounts'] = $db->table('users')->whereIn('id', $ids)->count();
    $counts['permission_pivots'] = $db->table('model_has_permissions')->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $ids)->count();
    if ($counts['accounts'] !== 18 || $counts['permission_pivots'] !== 154 || $db->table('nationwide_access_grants')->whereIn('user_id', $ids)->exists()) {
        throw new RuntimeException('Actual account/pivot/nationwide mismatch.');
    }
    foreach ($ledger['regions'] as $label => $id) {
        $r = Region::findOrFail($id);
        if ($r->code !== 'uiux_20261027_a01_'.$label || ! $r->is_active) {
            throw new RuntimeException('Actual region mismatch');
        }
    }
    foreach ($ledger['institutions'] as $label => $id) {
        $r = Institution::findOrFail($id);
        if ($r->name !== 'uiux_20261027_a01 Fictional '.$label || ! $r->is_synthetic || $r->primary_region_id !== $ledger['regions']['primary']) {
            throw new RuntimeException('Actual institution mismatch');
        }
    }
    foreach (['representation' => 'institution_representatives', 'eligibility' => 'applicant_sandbox_eligibility_grants', 'region_assignments' => 'user_region_assignments', 'reviewer_assignments' => 'synthetic_application_reviewer_assignments'] as $key => $table) {
        $rows = $db->table($table)->whereIn('id', array_column($ledger[$key], 'id'))->get()->keyBy('id');
        $counts[$key] = $rows->count();
        if ($counts[$key] !== count($ledger[$key])) {
            throw new RuntimeException('Actual relationship count mismatch: '.$key);
        }
        foreach ($ledger[$key] as $expected) {
            $actual = (array) $rows[$expected['id']];
            foreach ($expected as $field => $value) {
                if (in_array($field, ['created_at', 'updated_at', 'starts_at', 'ends_at', 'verified_at'], true)) {
                    if ((new DateTimeImmutable($actual[$field])) != (new DateTimeImmutable((string) $value))) {
                        throw new RuntimeException('Actual relationship timestamp mismatch');
                    }
                } elseif (($actual[$field] ?? null) != $value) {
                    throw new RuntimeException('Actual relationship mismatch: '.$key.'.'.$field);
                }
            }
            if (($actual['granted_by'] ?? null) !== $ledger['accounts']['osds']['id']) {
                throw new RuntimeException('Unexpected foundational grantor');
            }
        }
    }
    $counts['regions'] = $db->table('regions')->whereIn('id', array_values($ledger['regions']))->count();
    $counts['institutions'] = $db->table('institutions')->whereIn('id', array_values($ledger['institutions']))->count();

    return $counts;
}

$pdo = null;
$result = ['stage' => 'B2', 'mode' => $mode, 'status' => 'BLOCKED', 'writes_started' => false];
try {
    if (is_file($root.'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached configuration prohibited.');
    }
    foreach (['DB_URL', 'DATABASE_URL', 'APP_CONFIG_CACHE', 'PGHOST', 'PGPORT', 'PGDATABASE', 'PGSERVICE', 'PGSERVICEFILE'] as $key) {
        if (($v = getenv($key)) !== false && $v !== '') {
            throw new RuntimeException('Unexpected environment override '.$key);
        }
    }
    $env = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    if (($env['DB_HOST'] ?? '') !== '127.0.0.1' || (string) ($env['DB_PORT'] ?? '') !== '55432' || ($env['DB_CONNECTION'] ?? '') !== 'pgsql' || ! empty($env['DB_URL'])) {
        throw new RuntimeException('Testing credential endpoint invalid.');
    }
    $fixed = ['APP_ENV' => 'sandbox', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '55432', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'DATABASE_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync'];
    foreach ($fixed as $key => $value) {
        if (str_starts_with($key, 'DB_') && ($old = getenv($key)) !== false && $old !== $value) {
            throw new RuntimeException('Unexpected ambient target '.$key);
        }
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    foreach (['DB_USERNAME', 'DB_PASSWORD'] as $key) {
        $value = $env[$key] ?? '';
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    $config = ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => '55432', 'database' => 'osds_ui_browser_test', 'username' => $env['DB_USERNAME'], 'password' => $env['DB_PASSWORD'] ?? '', 'charset' => 'utf8', 'timezone' => 'UTC', 'search_path' => 'public', 'sslmode' => 'prefer'];
    b2Target($config);
    $result['configured'] = array_intersect_key($config, array_flip(['driver', 'host', 'port', 'database']));
    $pdo = (new PostgresConnector)->connect($config);
    b2Identity($pdo);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $result['transaction_read_only'] = $pdo->query('SHOW transaction_read_only')->fetchColumn();
    if ($result['transaction_read_only'] !== 'on') {
        throw new RuntimeException('Read-only transaction not established.');
    }
    $pdo->exec("SET LOCAL TIME ZONE 'Asia/Manila'");
    $result['projection_timezone'] = $pdo->query('SHOW TimeZone')->fetchColumn();
    $result['gates']['identity_and_read_only'] = 'PASS';
    $pdo->exec("SET LOCAL statement_timeout='15s'");
    $pdo->exec("SET LOCAL lock_timeout='2s'");
    // Reuse only the reviewed projection/function definitions, not the helper's CLI entry point.
    $helper = file_get_contents($root.'/scripts/Run-UiUxAcceptance.php');
    $prefix = substr($helper, 0, strpos($helper, '$root = dirname(__DIR__);'));
    eval(substr($prefix, strpos($prefix, 'function uiuxCompatibilityProjections')));
    $compatibility = [];
    try {
        uiuxCompatibility($pdo, $compatibility);
    } finally {
        file_put_contents($dir.'/observed-projections.json', json_encode($compatibility, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    $old = json_decode(file_get_contents($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json'), true, flags: JSON_THROW_ON_ERROR);
    $differences = [];
    $diff = function ($before, $current, string $path) use (&$diff, &$differences): void {
        if (is_array($before) && is_array($current)) {
            foreach (array_unique([...array_keys($before), ...array_keys($current)]) as $key) {
                if (! array_key_exists($key, $before) || ! array_key_exists($key, $current)) {
                    $differences[] = ['path' => $path.'.'.$key, 'historical_present' => array_key_exists($key, $before), 'current_present' => array_key_exists($key, $current)];
                } else {
                    $diff($before[$key], $current[$key], $path.'.'.$key);
                }
            }
        } elseif ($before !== $current) {
            $differences[] = ['path' => $path, 'historical' => $before, 'current' => $current];
        }
    };
    $diff($old['compatibility']['data'], $compatibility['data'], 'data');
    $result['raw_projection_equality'] = $compatibility['data'] == $old['compatibility']['data'];
    $result['php_strict_projection_equality'] = $compatibility['data'] === $old['compatibility']['data'];
    $result['field_differences'] = $differences;
    $result['source_hashes'] = ['b0_helper' => hash('sha256', $helper), 'b2_helper' => hash_file('sha256', __FILE__), 'b0_log' => hash_file('sha256', $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json')];
    file_put_contents($dir.'/projection-comparison.json', json_encode(array_intersect_key($result, array_flip(['raw_projection_equality', 'php_strict_projection_equality', 'field_differences', 'source_hashes'])), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    if ($compatibility['data'] != $old['compatibility']['data']) {
        throw new RuntimeException('Protected B0 configuration projections changed.');
    }
    $result['gates']['original_b0_projection_equality'] = 'PASS';
    $preview = $pdo->query('SELECT id,module_id,form_version_id,requirement_set_version_id,configuration_review_approval_id,channel FROM publication_manifests WHERE id=128')->fetch(PDO::FETCH_ASSOC);
    if (! $preview || (int) $preview['module_id'] !== 1 || (int) $preview['form_version_id'] !== 466 || (int) $preview['requirement_set_version_id'] !== 466 || (int) $preview['configuration_review_approval_id'] !== 137 || $preview['channel'] !== 'review_sandbox') {
        throw new RuntimeException('Preview publication changed.');
    }
    if ($pdo->query('SELECT count(*) FROM publication_manifest_retirements WHERE publication_manifest_id=128')->fetchColumn() !== 0) {
        throw new RuntimeException('Staff preview retired.');
    }
    $result['gates']['staff_preview_128'] = 'PASS';
    $ids = $pdo->query("SELECT id FROM demonstration_document_versions WHERE kind='personnel' AND state='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    if (array_map('intval', $ids) !== [2, 4, 6, 8, 12, 14, 16, 19, 22, 25, 28, 31, 40, 42, 44, 46]) {
        throw new RuntimeException('Published personnel slots changed.');
    }
    $result['gates']['published_personnel_slots'] = 'PASS';
    b2Namespace($pdo);
    $result['gates']['namespace_unused'] = 'PASS';
    $manifest = b2Manifest();
    $permissions = $pdo->query("SELECT id,name FROM permissions WHERE guard_name='web'")->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($manifest as $names) {
        if (array_diff($names, array_values($permissions))) {
            throw new RuntimeException('Missing existing permission definitions.');
        }
    }
    $result['gates']['existing_permission_definitions'] = 'PASS';
    $eligibleGrantors = array_keys(array_filter($manifest, static fn ($names) => in_array('applicant.sandbox.publish', $names, true)));
    if (count($manifest) !== 18 || $eligibleGrantors !== ['osds']) {
        throw new RuntimeException('Amended grantor dependency mismatch.');
    }
    foreach ($manifest as $names) {
        if (in_array('questionnaires.retire', $names, true) || array_diff($names, array_column(FoundationPermission::cases(), 'value'))) {
            throw new RuntimeException('Manifest contains forbidden/unknown permission.');
        }
    }
    $result['planned_operations'] = ['accounts' => 18, 'regions' => 2, 'institutions' => 3, 'representation' => 3, 'eligibility' => 6, 'region_assignments' => 12, 'reviewer_assignments' => 20, 'roles' => 0, 'nationwide_grants' => 0, 'office_revisions' => 0, 'personnel_versions' => 0, 'eligibility_grantor' => 'osds'];
    $result['gates']['amended_manifest_source_dependencies'] = 'PASS';
    $tables = ['users', 'regions', 'institutions', 'institution_representatives', 'applicant_sandbox_eligibility_grants', 'user_region_assignments', 'synthetic_application_reviewer_assignments', 'model_has_permissions', 'audit_events'];
    foreach ($tables as $table) {
        if (! $pdo->query("SELECT to_regclass('public.$table')")->fetchColumn()) {
            throw new RuntimeException('Missing foundational table '.$table);
        }
    }
    $result['schema_constraints'] = $pdo->query("SELECT conrelid::regclass::text AS table_name,conname,contype,convalidated,pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE connamespace='public'::regnamespace ORDER BY conrelid,conname")->fetchAll(PDO::FETCH_ASSOC);
    $result['indexes'] = $pdo->query("SELECT tablename,indexname,indexdef FROM pg_indexes WHERE schemaname='public' ORDER BY tablename,indexname")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result['schema_constraints'] as $constraint) {
        if (! $constraint['convalidated']) {
            throw new RuntimeException('Unvalidated constraint: '.$constraint['conname']);
        }
    }
    foreach ($tables as $table) {
        $constraints = array_filter($result['schema_constraints'], static fn ($row) => $row['table_name'] === $table);
        if (! in_array('p', array_column($constraints, 'contype'), true)) {
            throw new RuntimeException('Missing primary key: '.$table);
        }
        if (! in_array($table, ['users', 'regions'], true) && ! in_array('f', array_column($constraints, 'contype'), true)) {
            throw new RuntimeException('Missing foundational foreign key: '.$table);
        }
    }
    foreach (['active_user_region_assignment_unique', 'active_institution_representative_unique', 'active_applicant_sandbox_eligibility_unique'] as $index) {
        if (! in_array($index, array_column($result['indexes'], 'indexname'), true)) {
            throw new RuntimeException('Missing required unique index: '.$index);
        }
    }
    $result['gates']['schema_foreign_keys_unique_constraints'] = 'PASS';
    $result['migrations'] = $pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(PDO::FETCH_COLUMN);
    $local = array_map(static fn ($p) => basename($p, '.php'), glob($root.'/database/migrations/*.php'));
    if (array_diff($local, $result['migrations']) || array_diff($result['migrations'], $local)) {
        throw new RuntimeException('Migration ledger differs; migrations prohibited.');
    }
    $result['gates']['migration_ledger'] = 'PASS';
    // Full-row digest snapshots must use the same UTC representation as the
    // later application/write connections; B0 projection comparison above
    // retains its transaction-local historical Asia/Manila representation.
    $pdo->exec("SET LOCAL TIME ZONE 'UTC'");
    $protected = b2Protected($pdo);
    $result['protected_before'] = $protected;
    $result['preview'] = $preview;
    $result['permissions'] = $manifest;
    $result['preflight'] = 'PASS';
    $result['closing_read_only'] = $pdo->query('SHOW transaction_read_only')->fetchColumn();
    if ($result['closing_read_only'] !== 'on') {
        throw new RuntimeException('Read-only state changed.');
    }
    $pdo->rollBack();
    $result['transaction_closed'] = 'ROLLBACK';
    $result['timezone_after_rollback'] = $pdo->query('SHOW TimeZone')->fetchColumn();
    file_put_contents($dir.'/preflight-'.$mode.'.json', json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    if ($mode === 'inspect') {
        $result['status'] = 'PASS';
    } else {
        if (! mkdir($dir.'/write-attempt', 0700)) {
            throw new RuntimeException('Write attempt already reserved; no retry.');
        }
        file_put_contents($dir.'/write-attempt/started.json', json_encode(['attempt' => 'b2-foundation-attempt-01', 'started' => gmdate('c')], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        Connection::resolverFor('pgsql', static function ($unused, $database, $prefix, $c) {
            b2Target($c);

            return new PostgresConnection(static function () use ($c) {
                b2Target($c);
                $p = (new PostgresConnector)->connect($c);
                b2Identity($p);

                return $p;
            }, $database, $prefix, $c);
        });
        foreach (['sqlite', 'mysql', 'mariadb', 'sqlsrv'] as $driver) {
            Connection::resolverFor($driver, static function () {
                throw new RuntimeException('B2 rejects other drivers.');
            });
        }
        $app = require $root.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $db = DB::connection();
        b2Identity($db->getPdo());
        $db->beginTransaction();
        $db->statement("SET LOCAL lock_timeout='2s'");
        $db->statement("SET LOCAL statement_timeout='15s'");
        $db->select('SELECT pg_advisory_xact_lock(20261027, 201)');
        b2Namespace($db->getPdo());
        if (b2Protected($db->getPdo()) !== $protected) {
            throw new RuntimeException('Protected data changed before writes.');
        }
        $result['writes_started'] = true;
        $ledger = ['namespace' => 'uiux_20261027_a01', 'database' => 'osds_ui_browser_test', 'accounts' => [], 'regions' => [], 'institutions' => [], 'representation' => [], 'eligibility' => [], 'region_assignments' => [], 'reviewer_assignments' => []];
        $credentials = [];
        foreach ($manifest as $label => $names) {
            $password = bin2hex(random_bytes(24));
            $u = User::create(['name' => 'uiux_20261027_a01 '.$label, 'email' => $label.'@uiux-20261027-a01.invalid', 'password' => $password, 'status' => $label === 'inactive' ? 'inactive' : 'active', 'is_synthetic' => true]);
            $u->givePermissionTo($names);
            $actual = $u->getDirectPermissions()->pluck('name')->sort()->values()->all();
            if ($actual !== $names || $u->roles()->exists()) {
                throw new RuntimeException('Permission/role mismatch');
            }
            $users[$label] = $u;
            $ledger['accounts'][$label] = ['id' => $u->id, 'email' => $u->email, 'permissions' => $actual, 'status' => $u->status];
            $ledger['accounts'][$label]['permission_ids'] = $u->getDirectPermissions()->pluck('id')->all();
            $credentials[$label] = ['email' => $u->email, 'password' => $password];
        }
        $start = now()->subDay();
        $end = now()->addDays(30);
        $ledger['effective_from'] = $start->toIso8601String();
        $ledger['effective_until'] = $end->toIso8601String();
        foreach (['primary', 'probe'] as $label) {
            $regions[$label] = Region::create(['code' => 'uiux_20261027_a01_'.$label, 'name' => 'uiux_20261027_a01 Fictional '.$label, 'is_active' => true]);
            $ledger['regions'][$label] = $regions[$label]->id;
        }
        $modules = OsdsModule::whereIn('code', ['registration', 'acsa'])->get()->keyBy('code');
        $ledger['modules'] = $modules->pluck('id', 'code')->all();
        foreach (['osds', 'evaluator', 'chief', 'director', 'evidence-reviewer', 'read-only', 'combined', 'inactive', 'revocation', 'wrong-region', 'cor-only', 'acsa-only'] as $label) {
            $region = $regions[$label === 'wrong-region' ? 'probe' : 'primary'];
            $row = UserRegionAssignment::create(['user_id' => $users[$label]->id, 'region_id' => $region->id, 'starts_at' => $start, 'ends_at' => $end, 'granted_by' => $users['osds']->id]);
            $ledger['region_assignments'][] = $row->getAttributes();
            if ($label === 'evidence-reviewer') {
                continue;
            }
            foreach ($modules as $code => $module) {
                if (($label === 'cor-only' && $code !== 'registration') || ($label === 'acsa-only' && $code !== 'acsa')) {
                    continue;
                }
                $row = SyntheticApplicationReviewerAssignment::create(['reviewer_id' => $users[$label]->id, 'region_id' => $region->id, 'module_id' => $module->id, 'starts_at' => $start, 'ends_at' => $end, 'granted_by' => $users['osds']->id, 'reason' => 'uiux_20261027_a01 approved exact-pair foundation']);
                $ledger['reviewer_assignments'][] = $row->getAttributes();
            }
        }
        foreach (['owner', 'auxiliary', 'unrelated'] as $label) {
            $inst = Institution::create(['name' => 'uiux_20261027_a01 Fictional '.$label, 'primary_region_id' => $regions['primary']->id, 'organization_type' => 'athletic_association', 'is_synthetic' => true]);
            $ledger['institutions'][$label] = $inst->id;
            $rep = InstitutionRepresentative::create(['institution_id' => $inst->id, 'user_id' => $users[$label === 'unrelated' ? 'unrelated' : 'applicant']->id, 'starts_at' => $start, 'ends_at' => $end, 'verified_at' => now(), 'verified_by' => $users['osds']->id, 'granted_by' => $users['osds']->id]);
            $ledger['representation'][] = $rep->getAttributes();
            foreach ($modules as $module) {
                $grant = ApplicantSandboxEligibilityGrant::create(['institution_id' => $inst->id, 'module_id' => $module->id, 'granted_by' => $users['osds']->id, 'starts_at' => $start, 'ends_at' => $end, 'reason' => 'uiux_20261027_a01 approved synthetic foundation']);
                $ledger['eligibility'][] = $grant->getAttributes();
            }
        }
        if (b2Protected($db->getPdo()) !== $protected) {
            throw new RuntimeException('Protected configuration changed');
        }
        foreach (['accounts' => 18, 'regions' => 2, 'institutions' => 3, 'representation' => 3, 'eligibility' => 6, 'region_assignments' => 12, 'reviewer_assignments' => 20] as $key => $expected) {
            if (count($ledger[$key]) !== $expected) {
                throw new RuntimeException('Fixture cardinality mismatch: '.$key);
            }
        }
        $ledger['audit_records'] = $db->select('SELECT id,action,resource_type,resource_id FROM audit_events WHERE xmin::text=pg_current_xact_id()::text ORDER BY id');
        $result['actual_before_commit'] = b2VerifyFoundation($db, $ledger, $manifest);
        $expectedWrites = ['users' => 18, 'regions' => 2, 'institutions' => 3, 'institution_representatives' => 3, 'applicant_sandbox_eligibility_grants' => 6, 'user_region_assignments' => 12, 'synthetic_application_reviewer_assignments' => 20, 'model_has_permissions' => 154, 'audit_events' => count($ledger['audit_records'])];
        $result['transaction_row_writes'] = [];
        foreach ($db->select("SELECT tablename FROM pg_tables WHERE schemaname='public'") as $table) {
            $name = $table->tablename;
            if (! preg_match('/^[a-z_]+$/D', $name)) {
                throw new RuntimeException('Unexpected table identifier');
            }
            $n = (int) $db->selectOne('SELECT count(*) AS n FROM "'.$name.'" WHERE xmin::text=pg_current_xact_id()::text')->n;
            if ($n !== ($expectedWrites[$name] ?? 0)) {
                throw new RuntimeException('Unexpected transaction writes: '.$name);
            }
            if ($n) {
                $result['transaction_row_writes'][$name] = $n;
            }
        }
        b2Identity($db->getPdo());
        file_put_contents($dir.'/write-attempt/credentials.json', json_encode($credentials, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        file_put_contents($dir.'/write-attempt/pending-ledger.json', json_encode($ledger, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $db->commit();
        $ledger['commit'] = 'confirmed-return';
        file_put_contents($dir.'/write-attempt/ownership-ledger.json', json_encode($ledger, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $result['transaction'] = 'COMMIT';
        $result['actual_after_commit'] = b2VerifyFoundation($db, $ledger, $manifest);
        $result['protected_after'] = b2Protected($db->getPdo());
        if ($result['protected_after'] !== $protected) {
            throw new RuntimeException('Protected data changed after commit; inspect without repair.');
        }
        $result['status'] = 'PASS';
    }
} catch (Throwable $e) {
    if (isset($db) && $db->transactionLevel() > 0) {
        $db->rollBack();
        $result['transaction'] = 'ROLLBACK returned; inspect if commit was attempted';
    }
    if ($pdo?->inTransaction()) {
        $pdo->rollBack();
        $result['transaction_closed'] = 'ROLLBACK';
    }
    $result['error_class'] = $e::class;
    $result['error'] = $e->getMessage();
}
file_put_contents($dir.'/result-'.$mode.'.json', json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['status' => $result['status'], 'mode' => $mode, 'writes_started' => $result['writes_started'], 'error' => $result['error'] ?? null]).PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);
