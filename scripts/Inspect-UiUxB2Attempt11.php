<?php

// Verification only. Never invoke during source-only preparation.
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

if ($argc !== 2 || ! in_array($argv[1], ['prepare', 'preflight', 'postflight'], true)) {
    exit(2);
}
if ($argv[1] === 'prepare') {
    define('UIUX_B211_PREPARATION', true);
}
$stage = $argv[1] === 'prepare' ? 'preflight' : $argv[1];
require __DIR__.'/UiUxB2Attempt11Bootstrap.php';
$file = $dir.'/'.$stage.'.json';
if (file_exists($file)) {
    throw new RuntimeException('Fixed inspection already exists; no retry');
}
$read = static fn ($path) => json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
$previous = $read($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel-continuation/post-stop-drafts.json');
$ledger = $read($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/write-attempt/ownership-ledger.json');
$out = ['status' => 'BLOCKED', 'stage' => $stage, 'checks' => [], 'personnel' => []];
$db = DB::connection();
$p = $db->getPdo();
$p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
try {
    $p->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $out['identity'] = b2Identity($p);
    $out['isolation'] = $p->query('SHOW transaction_isolation')->fetchColumn();
    $out['default_read_only'] = $p->query('SHOW default_transaction_read_only')->fetchColumn();
    if ($p->query('SHOW transaction_read_only')->fetchColumn() !== 'on') {
        throw new RuntimeException('Read-only required');
    }
    $out['read_only_before'] = 'on';
    $out['foundation'] = b2VerifyFoundation($db, $ledger, b2Manifest());
    $reviewer = $ledger['accounts']['config-reviewer']['id'];
    $out['reviewer_id'] = $reviewer;
    $p->exec("SET LOCAL TIME ZONE 'Asia/Manila'");
    $helper = file_get_contents($root.'/scripts/Run-UiUxAcceptance.php');
    $prefix = substr($helper, 0, strpos($helper, '$root = dirname(__DIR__);'));
    eval(substr($prefix, strpos($prefix, 'function uiuxCompatibilityProjections')));
    $compat = [];
    uiuxCompatibility($p, $compat);
    $b0 = $read($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json');
    if ($compat['data'] !== $b0['compatibility']['data']) {
        throw new RuntimeException('Complete B0 projection mismatch');
    }
    $out['checks']['b0'] = 'PASS';
    $p->exec("SET LOCAL TIME ZONE 'UTC'");
    $out['protected'] = b2Protected($p);
    $expectedProtected = $previous['protected'];
    // Only these two rows may change state, lock and update timestamp.
    $actualProtected = $out['protected'];
    $actualProtected['demonstration_document_versions'] = array_values(array_filter($actualProtected['demonstration_document_versions'], fn ($r) => ! in_array((int) $r['id'], [47, 48], true)));
    $expectedProtected['demonstration_document_versions'] = array_values(array_filter($expectedProtected['demonstration_document_versions'], fn ($r) => ! in_array((int) $r['id'], [47, 48], true)));
    if ($actualProtected !== $expectedProtected) {
        throw new RuntimeException('Unrelated protected configuration changed');
    }
    $out['checks']['protected_rows'] = 'PASS';
    $out['all_version_digests'] = $p->query('SELECT id,md5(to_jsonb(v)::text) AS digest FROM demonstration_document_versions v WHERE id NOT IN (47,48) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if ($stage === 'postflight') {
        $before = $read($dir.'/preflight.json');
        if ($out['all_version_digests'] !== $before['all_version_digests']) {
            throw new RuntimeException('Unrelated document version added, removed or changed');
        }
    }
    foreach ($previous['counts'] as $table => $count) {
        $out['counts'][$table] = (int) $p->query('SELECT count(*) FROM '.$table)->fetchColumn();
        if ($out['counts'][$table] !== $count) {
            throw new RuntimeException('Business/permission count changed: '.$table);
        }
    }
    $out['checks']['business_counts'] = 'PASS';
    $directory = $db->table('demo_office_directory_revisions')->where('id', 26)->first();
    $offices = json_decode($directory->offices ?? '{}', true, flags: JSON_THROW_ON_ERROR);
    if ($offices !== $previous['directory']['offices'] || (int) $db->table('demo_office_directory_revisions')->max('id') !== 26) {
        throw new RuntimeException('Directory 26 changed or superseded');
    }
    $out['office_key'] = $previous['office_key'];
    $out['office_label'] = 'uiux_20261027_a01 Fictional primary office';
    $out['primary_region'] = $ledger['regions']['primary'];
    $out['region_label'] = $db->table('regions')->where('id', $out['primary_region'])->value('name');
    $out['checks']['directory_26'] = 'PASS';
    foreach ([47, 48] as $id) {
        $v = (array) $db->table('demonstration_document_versions')->where('id', $id)->first();
        if (! $v) {
            throw new RuntimeException('Fixed version missing: '.$id);
        }
        $old = array_values(array_filter($previous['personnel'], fn ($r) => (int) $r['id'] === $id))[0];
        if ($v['module_id'] !== ($id === 47 ? 1 : 2) || $v['region_id'] !== $ledger['regions']['primary'] || $v['directory_revision_id'] !== 26
            || new DateTimeImmutable($v['created_at']) != new DateTimeImmutable($old['created_at'])) {
            throw new RuntimeException('Fixed identity/creation binding mismatch: '.$id);
        }
        foreach (['kind', 'module_id', 'region_id', 'directory_revision_id', 'version', 'content_hash', 'created_by'] as $field) {
            if ($v[$field] !== $old[$field]) {
                throw new RuntimeException('Binding changed: '.$id.'/'.$field);
            }
        }
        if (json_decode($v['content'], true, flags: JSON_THROW_ON_ERROR) !== $old['content']) {
            throw new RuntimeException('Content changed: '.$id);
        }
        $history = $db->table('demonstration_document_history')->where('version_id', $id)->orderBy('id')->get(['id', 'event', 'actor_id'])->map(fn ($r) => (array) $r)->all();
        $out['personnel'][] = ['id' => $id, 'module_id' => $v['module_id'], 'module_label' => $db->table('modules')->where('id', $v['module_id'])->value('name'), 'state' => $v['state'], 'lock_version' => $v['lock_version'], 'history' => $history];
        $events = array_column($history, 'event');
        $prefixes = ['draft' => ['version_created'], 'reviewed' => ['version_created', 'version_reviewed'], 'published' => ['version_created', 'version_reviewed', 'version_published']];
        if (! isset($prefixes[$v['state']]) || $events !== $prefixes[$v['state']] || $v['lock_version'] !== count($events)) {
            throw new RuntimeException('Lifecycle/lock mismatch: '.$id);
        }
        foreach ($history as $event) {
            if ($event['actor_id'] !== ($event['event'] === 'version_created' ? $ledger['accounts']['osds']['id'] : $reviewer)) {
                throw new RuntimeException('History actor mismatch: '.$id);
            }
        }
        $audit = $db->table('audit_events')->where('resource_type', 'App\\Models\\DemonstrationDocumentVersion')->where('resource_id', (string) $id)->orderBy('id')->get(['id', 'action', 'actor_id'])->map(fn ($r) => (array) $r)->all();
        $out['audit'][$id] = $audit;
        if (array_column($audit, 'action') !== array_map(fn ($e) => 'demonstration_pdf.'.$e, $events) || array_column($audit, 'actor_id') !== array_column($history, 'actor_id')) {
            throw new RuntimeException('Audit mismatch: '.$id);
        }
    }
    $states = array_column($out['personnel'], 'state');
    if ($states !== ['draft', 'draft']) {
        throw new RuntimeException('Both fixed drafts must be untouched');
    }
    $out['status'] = 'PASS'; // Login/navigation only: neither lifecycle may advance.
    $out['read_only'] = $p->query('SHOW transaction_read_only')->fetchColumn();
} catch (Throwable $error) {
    $out['error'] = $error->getMessage();
} finally {
    if ($p->inTransaction()) {
        $p->rollBack();
    }
    $out['transaction_closed'] = 'ROLLBACK';
    $handle = fopen($file, 'x');
    if (! $handle) {
        throw new RuntimeException('Inspection output collision');
    }
    fwrite($handle, json_encode($out, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    fclose($handle);
}
echo json_encode(['status' => $out['status'], 'stage' => $stage]).PHP_EOL;
exit($out['status'] === 'PASS' ? 0 : 1);
