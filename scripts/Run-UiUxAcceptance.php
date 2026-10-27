<?php

// Read-only Gate A/B0 only. No Laravel bootstrap, mutation or test execution.
declare(strict_types=1);

const UIUX_INVENTORY_HOST = '127.0.0.1';
const UIUX_INVENTORY_PORT = 55432;
const UIUX_INVENTORY_DATABASES = ['osds_test', 'osds_ui_browser_test'];

/** Fixed, named projections: no applicants, answers, locators or personal values. */
function uiuxCompatibilityProjections(): array
{
    return [
        'publications' => 'SELECT p.id,p.module_id,p.channel,p.form_version_id,p.requirement_set_version_id,p.configuration_review_approval_id,p.form_hash,p.requirement_hash,p.combined_hash,p.effective_from,p.effective_until,NOT EXISTS(SELECT 1 FROM public.publication_manifest_retirements r WHERE r.publication_manifest_id=p.id) AS unretired,p.effective_from<=CURRENT_TIMESTAMP AND (p.effective_until IS NULL OR p.effective_until>CURRENT_TIMESTAMP) AS within_dates FROM public.publication_manifests p WHERE p.id IN (198,199) ORDER BY p.id',
        'definitions' => 'SELECT id,module_id,version,state,source_form_version_id,configuration_review_approval_id,definition_hash FROM public.issuance_definitions WHERE id IN (54,55) ORDER BY id',
        'templates' => 'SELECT id,module_id,region_id,kind,version,state,content_hash FROM public.demonstration_document_versions WHERE id IN (43,45) ORDER BY id',
        'modules' => 'SELECT id,code,is_active FROM public.modules WHERE id IN (1,2) ORDER BY id',
        'forms' => 'SELECT v.id,v.form_definition_id,v.version_number,v.state,v.configuration_hash,d.module_id,d.audience FROM public.form_versions v JOIN public.form_definitions d ON d.id=v.form_definition_id WHERE v.id IN (591,592) ORDER BY v.id',
        'requirement_sets' => 'SELECT id,module_id,version_number,state,configuration_hash FROM public.requirement_set_versions WHERE id IN (591,592) ORDER BY id',
        'sections' => 'SELECT id,form_version_id,key,sort_order FROM public.form_sections WHERE form_version_id IN (591,592) ORDER BY form_version_id,sort_order',
        'questions' => 'SELECT id,form_version_id,form_section_id,key,type,is_required,sort_order,validation_rules,condition_rules FROM public.form_questions WHERE form_version_id IN (591,592) ORDER BY form_version_id,form_section_id,sort_order',
        'options' => 'SELECT o.id,o.form_question_id,o.key,o.sort_order FROM public.form_question_options o JOIN public.form_questions q ON q.id=o.form_question_id WHERE q.form_version_id IN (591,592) ORDER BY o.form_question_id,o.sort_order',
        'requirements' => 'SELECT id,requirement_set_version_id,key,is_required,minimum_evidence,maximum_evidence,sort_order,condition_rules FROM public.documentary_requirements WHERE requirement_set_version_id IN (591,592) ORDER BY requirement_set_version_id,sort_order',
        'providers' => 'SELECT p.documentary_requirement_id,t.id AS provider_id,t.code,t.is_enabled FROM public.documentary_requirement_provider p JOIN public.documentary_requirements r ON r.id=p.documentary_requirement_id JOIN public.evidence_provider_types t ON t.id=p.evidence_provider_type_id WHERE r.requirement_set_version_id IN (591,592) ORDER BY p.documentary_requirement_id,t.id',
        'reviews' => 'SELECT a.id AS approval_id,a.outcome,a.approved_hash,r.id AS review_id,r.module_id,r.form_version_id,r.requirement_set_version_id,r.state,r.form_hash,r.requirement_hash,r.combined_hash FROM public.configuration_review_approvals a JOIN public.configuration_review_requests r ON r.id=a.configuration_review_request_id WHERE a.id IN (264,265,266,267) ORDER BY a.id',
        'definition_structures' => "SELECT d.id,d.definition->'source_form_id' AS source_form_id,d.definition->'name_question_id' AS name_question_id,d.definition->'address_question_id' AS address_question_id,d.definition->>'approved_configuration_hash' AS approved_configuration_hash,jsonb_typeof(d.definition->'questions') AS questions_type,jsonb_typeof(d.definition->'requirements') AS requirements_type,(SELECT jsonb_agg(jsonb_build_object('key',q->'key','type',q->'type','required',q->'required','condition',q->'condition','validation',q->'validation','options',(SELECT jsonb_agg(jsonb_build_object('key',o->'key')) FROM jsonb_array_elements(CASE WHEN jsonb_typeof(q->'options')='array' THEN q->'options' ELSE '[]'::jsonb END) o))) FROM jsonb_array_elements(CASE WHEN jsonb_typeof(d.definition->'questions')='array' THEN d.definition->'questions' ELSE '[]'::jsonb END) q) AS questions,(SELECT jsonb_agg(jsonb_build_object('key',r->'key','required',r->'required','minimum',r->'minimum','maximum',r->'maximum','condition',r->'condition')) FROM jsonb_array_elements(CASE WHEN jsonb_typeof(d.definition->'requirements')='array' THEN d.definition->'requirements' ELSE '[]'::jsonb END) r) AS requirements FROM public.issuance_definitions d WHERE d.id IN (54,55) ORDER BY d.id",
        'template_structures' => "SELECT id,content->>'layout' AS layout,content->'fields' AS fields,jsonb_typeof(content->'fields') AS fields_type,jsonb_typeof(content->'wording')='string' AS wording_is_string,char_length(content->>'wording') AS wording_length,COALESCE((content->>'wording') ~* '[<>]|https?://',true) AS wording_has_markup_or_link FROM public.demonstration_document_versions WHERE id IN (43,45) ORDER BY id",
        'personnel_roots' => 'SELECT id,module_id,kind,region_id,directory_revision_id,version,state FROM public.demonstration_document_versions WHERE id IN (2,4,6,8,12,14,16,19,22,25,28,31,40,42,44,46) ORDER BY id',
        'personnel_structure' => "SELECT d.id,d.module_id,d.region_id,d.directory_revision_id,r.is_active AS region_active,dr.id IS NOT NULL AS directory_exists,jsonb_typeof(d.content->'office')='object' AS office_is_object,COALESCE(jsonb_typeof(d.content#>'{office,label}')='string' AND length(btrim(d.content#>>'{office,label}'))>0,false) AS label_complete,COALESCE(jsonb_typeof(d.content#>'{office,director}')='string' AND length(btrim(d.content#>>'{office,director}'))>0,false) AS director_complete,COALESCE(jsonb_typeof(d.content#>'{office,designation}')='string' AND length(btrim(d.content#>>'{office,designation}'))>0,false) AS designation_complete,COALESCE(jsonb_typeof(dr.offices->(d.content->>'office_key'))='object',false) AS directory_entry_exists,COALESCE(d.content->'office'=dr.offices->(d.content->>'office_key'),false) AS office_snapshot_matches FROM public.demonstration_document_versions d LEFT JOIN public.regions r ON r.id=d.region_id LEFT JOIN public.demo_office_directory_revisions dr ON dr.id=d.directory_revision_id WHERE d.id IN (2,4,6,8,12,14,16,19,22,25,28,31,40,42,44,46) ORDER BY d.id",
    ];
}

function uiuxCompatibility(PDO $pdo, array &$out): void
{
    $sql = uiuxCompatibilityProjections();
    $fetch = static function (string $name) use ($pdo, $sql, &$out): array {
        $rows = $pdo->query($sql[$name])->fetchAll();
        $out['queries_executed'][] = $name;
        $out['data'][$name] = $rows;

        return $rows;
    };
    $index = static fn (array $rows): array => array_column($rows, null, 'id');
    $pubs = $index($fetch('publications'));
    $defs = $index($fetch('definitions'));
    $templates = $index($fetch('templates'));
    foreach ([[198, 1, 591, 264], [199, 2, 592, 265]] as [$id, $module, $form, $approval]) {
        $p = $pubs[$id] ?? null;
        if (! $p || (int) $p['module_id'] !== $module || (int) $p['form_version_id'] !== $form || (int) $p['requirement_set_version_id'] !== $form
            || (int) $p['configuration_review_approval_id'] !== $approval || $p['channel'] !== 'applicant_sandbox' || ! $p['unretired'] || ! $p['within_dates']) {
            throw new RuntimeException('Fixed publication '.$id.' missing, changed or unavailable. No dependent structures followed.');
        }
    }
    foreach ([[54, 1, 591, 266], [55, 2, 592, 267]] as [$id, $module, $form, $approval]) {
        $d = $defs[$id] ?? null;
        if (! $d || (int) $d['module_id'] !== $module || (int) $d['source_form_version_id'] !== $form || (int) $d['configuration_review_approval_id'] !== $approval || $d['state'] !== 'published') {
            throw new RuntimeException('Fixed definition '.$id.' missing, changed or unpublished. No dependent structures followed.');
        }
    }
    foreach ([[43, 1], [45, 2]] as [$id, $module]) {
        $t = $templates[$id] ?? null;
        if (! $t || (int) $t['module_id'] !== $module || $t['kind'] !== 'template' || $t['state'] !== 'published' || $t['region_id'] !== null) {
            throw new RuntimeException('Fixed template '.$id.' missing, changed or unpublished. No dependent structures followed.');
        }
    }
    $out['fixed_roots'] = 'PASS';
    foreach (['modules', 'forms', 'requirement_sets', 'sections', 'questions', 'options', 'requirements', 'providers', 'reviews', 'definition_structures', 'template_structures'] as $name) {
        $fetch($name);
    }
    $personnel = $index($fetch('personnel_roots'));
    $expectedPersonnel = [2 => [1, 370, 10], 4 => [1, 373, 11], 6 => [1, 374, 12], 8 => [1, 375, 13], 12 => [1, 377, 15], 14 => [1, 378, 16], 16 => [1, 379, 17], 19 => [1, 380, 18], 22 => [1, 381, 19], 25 => [2, 382, 20], 28 => [1, 383, 21], 31 => [2, 384, 22], 40 => [1, 387, 24], 42 => [2, 387, 24], 44 => [1, 389, 25], 46 => [2, 389, 25]];
    $personnelMatch = true;
    foreach ($expectedPersonnel as $id => [$module, $region, $directory]) {
        $p = $personnel[$id] ?? null;
        $matches = $p && $p['kind'] === 'personnel' && $p['state'] === 'published' && (int) $p['module_id'] === $module && (int) $p['region_id'] === $region && (int) $p['directory_revision_id'] === $directory;
        $out['personnel_root_checks'][$id] = $matches ? 'PASS' : 'UNKNOWN';
        $personnelMatch = $personnelMatch && $matches;
    }
    if ($personnelMatch) {
        $fetch('personnel_structure');
    } else {
        $out['personnel_structure'] = 'UNKNOWN: fixed personnel graph changed; no directory dependencies followed';
    }
    foreach (['questions', 'definition_structures', 'template_structures'] as $name) {
        foreach ($out['data'][$name] as &$row) {
            foreach (['validation_rules', 'condition_rules', 'questions', 'requirements', 'fields'] as $field) {
                if (isset($row[$field]) && is_string($row[$field])) {
                    $row[$field] = json_decode($row[$field], true, flags: JSON_THROW_ON_ERROR);
                }
            }
        }
        unset($row);
    }
    foreach ($out['data']['requirements'] as &$row) {
        $row['condition_rules'] = $row['condition_rules'] === null ? null : json_decode($row['condition_rules'], true, flags: JSON_THROW_ON_ERROR);
    }
    unset($row);
    $out['content_hash_recomputation'] = 'UNKNOWN: full text payload fields excluded by approved projections';
    $out['runtime_success'] = 'BLOCKED: no fixtures, authentication or generation authorized';
}

$root = dirname(__DIR__);
$operation = $argv[1] ?? '';
$gate = $operation === 'compatibility' ? 'B0' : 'A';
$directory = $root.'/storage/app/private/uiux-consolidated-acceptance/'.($gate === 'B0' ? 'gate-b0' : 'gate-a');
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}
ini_set('display_errors', '0');
$target = '';
$pdo = null;
$result = ['gate' => $gate, 'status' => 'BLOCKED', 'connection_opened' => false, 'namespace' => 'uiux_20261027_a01'];
$exit = 1;

try {
    if (PHP_SAPI !== 'cli' || count($argv) !== 3 || ! in_array($operation, ['inventory', 'compatibility'], true)
        || ! preg_match('/^--database=(osds_test|osds_ui_browser_test)$/D', $argv[2], $match)) {
        throw new RuntimeException('Only exact inventory arguments or fixed-scope compatibility --database=osds_ui_browser_test are permitted. No connection attempted.');
    }
    $target = $match[1];
    if ($operation === 'compatibility' && $target !== 'osds_ui_browser_test') {
        throw new RuntimeException('Compatibility is permitted only for osds_ui_browser_test. No connection attempted.');
    }
    if (! in_array($target, UIUX_INVENTORY_DATABASES, true)) {
        throw new RuntimeException('Database is outside the Gate A allowlist.');
    }
    require $root.'/vendor/autoload.php';
    try {
        $environment = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    } catch (Throwable) {
        throw new RuntimeException('Testing credential configuration could not be parsed. No fallback permitted.');
    }
    if (($environment['DB_CONNECTION'] ?? '') !== 'pgsql'
        || ($environment['DB_HOST'] ?? '') !== UIUX_INVENTORY_HOST
        || (string) ($environment['DB_PORT'] ?? '') !== (string) UIUX_INVENTORY_PORT
        || ! empty($environment['DB_URL']) || empty($environment['DB_USERNAME'])) {
        throw new RuntimeException('Testing configuration does not match the approved PostgreSQL host/port and URL restrictions.');
    }
    // Database/host/port never come from ambient environment or a default DSN.
    $dsn = 'pgsql:host='.UIUX_INVENTORY_HOST.';port='.UIUX_INVENTORY_PORT.';dbname='.$target
        .";connect_timeout=5;options='-c default_transaction_read_only=on -c statement_timeout=15000 -c lock_timeout=2000'";
    $result['configured'] = ['driver' => 'pgsql', 'host' => UIUX_INVENTORY_HOST, 'port' => UIUX_INVENTORY_PORT, 'database' => $target];
    if ($operation === 'compatibility') {
        $result['query_projections'] = uiuxCompatibilityProjections();
        file_put_contents($directory.'/approved-projections-'.gmdate('Ymd-His').'.json', json_encode($result['query_projections'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    try {
        $pdo = new PDO($dsn, $environment['DB_USERNAME'], $environment['DB_PASSWORD'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Approved database unavailable (SQLSTATE '.preg_replace('/[^A-Z0-9]/i', '', (string) $e->getCode()).'). No fallback attempted.');
    }
    $result['connection_opened'] = true;
    $pdo->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $identity = $pdo->query("SELECT current_database() AS database, host(inet_server_addr()) AS server_address, inet_server_port() AS server_port, current_schema() AS schema, current_setting('server_version') AS server_version, current_setting('transaction_read_only') AS transaction_read_only, current_setting('default_transaction_read_only') AS default_transaction_read_only")->fetch();
    // Retain the non-sensitive observation even when a strict assertion fails.
    $identity['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $result['observed_identity'] = $identity;
    $expected = ['driver' => 'pgsql', 'database' => $target, 'server_address' => UIUX_INVENTORY_HOST,
        'server_port' => UIUX_INVENTORY_PORT, 'transaction_read_only' => 'on', 'default_transaction_read_only' => 'on'];
    $mismatches = [];
    foreach ($expected as $field => $value) {
        $observed = $field === 'server_port' ? (int) $identity[$field] : $identity[$field];
        $matches = $observed === $value;
        $result['identity_comparison'][$field] = ['expected' => $value, 'observed' => $observed, 'status' => $matches ? 'PASS' : 'FAIL'];
        if (! $matches) {
            $mismatches[] = $field;
        }
    }
    if ($mismatches) {
        throw new RuntimeException('Identity mismatch in: '.implode(', ', $mismatches).'. Inventory stopped.');
    }
    $result['identity'] = $identity;
    if ($operation === 'compatibility') {
        $result['compatibility'] = [];
        uiuxCompatibility($pdo, $result['compatibility']);
    } else {
        $tables = ['migrations', 'modules', 'regions', 'institutions', 'users', 'permissions', 'institution_representatives',
            'applicant_sandbox_eligibility_grants', 'synthetic_application_reviewer_assignments', 'synthetic_evidence_reviewer_assignments',
            'form_definitions', 'form_versions', 'requirement_set_versions', 'configuration_review_requests', 'configuration_review_approvals',
            'publication_manifests', 'publication_manifest_retirements', 'applications', 'application_submissions', 'application_state_history',
            'application_decision_stages', 'issuance_definitions', 'issuance_requests', 'issuance_history', 'demonstration_document_versions',
            'demonstration_documents', 'demonstration_document_attempts', 'demonstration_document_history',
            'downloadable_forms', 'downloadable_form_versions', 'demo_office_directory_revisions', 'audit_events'];
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $statement = $pdo->prepare("SELECT table_name, column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name IN ($placeholders) ORDER BY table_name, ordinal_position");
        $statement->execute($tables);
        $columns = $statement->fetchAll();
        $map = [];
        foreach ($columns as $column) {
            $map[$column['table_name']][] = $column['column_name'];
        }
        $has = static fn (string $table, array $required): bool => isset($map[$table]) && array_diff($required, $map[$table]) === [];
        $result['schema']['columns'] = $columns;
        $result['schema']['missing_tables'] = array_values(array_diff($tables, array_keys($map)));
        $statement = $pdo->prepare("SELECT tablename, indexname, indexdef FROM pg_indexes WHERE schemaname='public' AND tablename IN ($placeholders) ORDER BY tablename, indexname");
        $statement->execute($tables);
        $result['schema']['indexes'] = $statement->fetchAll();
        $statement = $pdo->prepare("SELECT r.relname AS table_name, c.conname AS name, c.contype AS type, c.convalidated AS validated, c.condeferrable AS deferrable, c.condeferred AS initially_deferred, pg_get_constraintdef(c.oid) AS definition FROM pg_constraint c JOIN pg_class r ON r.oid=c.conrelid JOIN pg_namespace n ON n.oid=r.relnamespace WHERE n.nspname='public' AND r.relname IN ($placeholders) ORDER BY r.relname,c.conname");
        $statement->execute($tables);
        $result['schema']['constraints'] = $statement->fetchAll();
        $statement = $pdo->prepare("SELECT r.relname AS table_name,t.tgname AS name,t.tgenabled AS enabled,pg_get_triggerdef(t.oid) AS definition FROM pg_trigger t JOIN pg_class r ON r.oid=t.tgrelid JOIN pg_namespace n ON n.oid=r.relnamespace WHERE n.nspname='public' AND NOT t.tgisinternal AND r.relname IN ($placeholders) ORDER BY r.relname,t.tgname");
        $statement->execute($tables);
        $result['schema']['triggers'] = $statement->fetchAll();
        $localMigrations = array_map(static fn ($file) => pathinfo($file, PATHINFO_FILENAME), glob($root.'/database/migrations/*.php'));
        sort($localMigrations);
        $applied = $has('migrations', ['migration', 'batch']) ? $pdo->query('SELECT migration,batch FROM public.migrations ORDER BY migration')->fetchAll() : [];
        $result['migrations'] = ['local_count' => count($localMigrations), 'applied' => $applied,
            'pending' => array_values(array_diff($localMigrations, array_column($applied, 'migration'))),
            'database_only' => array_values(array_diff(array_column($applied, 'migration'), $localMigrations))];
        $result['modules'] = $has('modules', ['id', 'code', 'is_active'])
            ? $pdo->query("SELECT id,code,is_active FROM public.modules WHERE code IN ('registration','acsa') ORDER BY code")->fetchAll() : [];
        $result['slots'] = [];
        if ($has('publication_manifests', ['id', 'module_id', 'channel', 'form_version_id', 'requirement_set_version_id', 'configuration_review_approval_id', 'effective_from', 'effective_until'])
            && $has('publication_manifest_retirements', ['publication_manifest_id']) && $has('modules', ['id', 'code'])) {
            $result['slots']['unretired_publications'] = $pdo->query("SELECT p.id,m.code AS module,p.channel,p.form_version_id,p.requirement_set_version_id,p.configuration_review_approval_id,p.effective_from,p.effective_until,CASE WHEN p.effective_from>CURRENT_TIMESTAMP THEN 'scheduled' WHEN p.effective_until IS NOT NULL AND p.effective_until<=CURRENT_TIMESTAMP THEN 'ended' ELSE 'within_date_window' END AS date_status FROM public.publication_manifests p JOIN public.modules m ON m.id=p.module_id WHERE m.code IN ('registration','acsa') AND NOT EXISTS(SELECT 1 FROM public.publication_manifest_retirements r WHERE r.publication_manifest_id=p.id) ORDER BY m.code,p.channel,p.id")->fetchAll();
        }
        if ($has('issuance_definitions', ['id', 'module_id', 'version', 'state', 'source_form_version_id', 'configuration_review_approval_id']) && $has('modules', ['id', 'code'])) {
            $result['slots']['published_definitions'] = $pdo->query("SELECT d.id,m.code AS module,d.version,d.state,d.source_form_version_id,d.configuration_review_approval_id FROM public.issuance_definitions d JOIN public.modules m ON m.id=d.module_id WHERE m.code IN ('registration','acsa') AND d.state='published' ORDER BY m.code,d.id")->fetchAll();
        }
        if ($has('demonstration_document_versions', ['id', 'kind', 'module_id', 'region_id', 'directory_revision_id', 'version', 'state']) && $has('modules', ['id', 'code'])) {
            $result['slots']['published_document_versions'] = $pdo->query("SELECT d.id,m.code AS module,d.kind,d.region_id,d.directory_revision_id,d.version,d.state FROM public.demonstration_document_versions d JOIN public.modules m ON m.id=d.module_id WHERE m.code IN ('registration','acsa') AND d.state='published' ORDER BY m.code,d.kind,d.region_id,d.id")->fetchAll();
        }
        $namespace = $result['namespace'];
        foreach (['users' => ['email', 'name'], 'institutions' => ['name'], 'regions' => ['code', 'name'], 'form_definitions' => ['name'], 'issuance_definitions' => ['title']] as $table => $fields) {
            $fields = array_values(array_intersect($fields, $map[$table] ?? []));
            if (! $fields || ! $has($table, ['id'])) {
                $result['namespace_checks'][$table] = ['status' => 'BLOCKED', 'reason' => 'Required metadata unavailable'];

                continue;
            }
            $predicates = implode(' OR ', array_map(static fn ($field) => "left($field,?)=?", $fields));
            $parameters = [];
            foreach ($fields as $field) {
                array_push($parameters, strlen($namespace), $namespace);
            }
            $statement = $pdo->prepare("SELECT id FROM public.$table WHERE $predicates ORDER BY id");
            $statement->execute($parameters);
            $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
            $result['namespace_checks'][$table] = ['status' => $ids ? 'CONFLICT' : 'PASS', 'matching_ids' => $ids];
        }
    }
    $result['transaction_read_only_before_close'] = $pdo->query('SHOW transaction_read_only')->fetchColumn();
    $pdo->rollBack();
    $result['transaction_closed'] = 'ROLLBACK';
    $result['status'] = 'PASS';
    $exit = 0;
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
        $result['transaction_closed'] = 'ROLLBACK';
    }
    // PDO errors may include values or SQL. Record code only, never raw text.
    $result['reason'] = $e instanceof PDOException
        ? 'Metadata query could not complete (SQLSTATE '.preg_replace('/[^A-Z0-9]/i', '', (string) $e->getCode()).').'
        : ($e instanceof RuntimeException ? $e->getMessage() : 'Inventory failed before completion; inspect helper compatibility.');
}
$result['recorded_at_utc'] = gmdate(DATE_ATOM);
$file = $directory.'/'.($target ?: 'rejected-input').'-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
file_put_contents($file, json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['gate' => $gate, 'database' => $target, 'status' => $result['status'], 'connection_opened' => $result['connection_opened'],
    'private_result' => basename($file), 'reason' => $result['reason'] ?? null], JSON_THROW_ON_ERROR).PHP_EOL;
exit($exit);
