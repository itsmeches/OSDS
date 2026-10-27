<?php

declare(strict_types=1);
use App\Models\DemonstrationDocumentVersion;
use App\Models\DemoOfficeDirectoryRevision;
use Illuminate\Support\Facades\DB;

if ($argc !== 2 || ! in_array($argv[1], ['preflight', 'directory', 'drafts', 'published', 'post-stop'], true)) {
    exit(2);
}
$stage = $argv[1];
require __DIR__.'/UiUxB2PersonnelBootstrap.php';
$file = $dir.'/'.$stage.'.json';
if (is_file($file)) {
    throw new RuntimeException('Inspection stage already recorded; no replay');
}
$db = DB::connection();
$p = $db->getPdo();
$p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$out = ['stage' => $stage, 'status' => 'BLOCKED'];
try {
    $p->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    b2Identity($p);
    if ($p->query('SHOW transaction_read_only')->fetchColumn() !== 'on') {
        throw new RuntimeException('Read-only required');
    }
    $ledger = json_decode(file_get_contents($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/write-attempt/ownership-ledger.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = b2Manifest();
    $out['foundation'] = b2VerifyFoundation($db, $ledger, $manifest);
    $p->exec("SET LOCAL TIME ZONE 'Asia/Manila'");
    $helper = file_get_contents($root.'/scripts/Run-UiUxAcceptance.php');
    $prefix = substr($helper, 0, strpos($helper, '$root = dirname(__DIR__);'));
    eval(substr($prefix, strpos($prefix, 'function uiuxCompatibilityProjections')));
    $compat = [];
    uiuxCompatibility($p, $compat);
    $b0 = json_decode(file_get_contents($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($compat['data'] !== $b0['compatibility']['data']) {
        throw new RuntimeException('B0 projections differ');
    }
    $p->exec("SET LOCAL TIME ZONE 'UTC'");
    $out['protected'] = b2Protected($p);
    $creation = json_decode(file_get_contents($root.'/storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/result-prepare.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($creation['protected_after'] as $table => $rows) {
        $current = array_column($out['protected'][$table], null, 'id');
        foreach ($rows as $row) {
            if (($current[$row['id']] ?? null) !== $row) {
                throw new RuntimeException('Old protected record changed: '.$table);
            }
        }
    }
    $out['counts'] = [];
    foreach (['applications', 'application_state_history', 'application_decision_stages', 'issuance_requests', 'demonstration_documents', 'permissions', 'model_has_roles', 'nationwide_access_grants'] as $table) {
        $out['counts'][$table] = (int) $p->query('SELECT count(*) FROM '.$table)->fetchColumn();
    }
    $latest = DemoOfficeDirectoryRevision::latest('id')->firstOrFail();
    $out['directory'] = ['id' => $latest->id, 'offices' => $latest->offices];
    foreach ($latest->offices as $office) {
        foreach (['label', 'director', 'designation'] as $key) {
            if (! is_string($office[$key] ?? null) || trim($office[$key]) === '') {
                throw new RuntimeException('Existing directory contains incomplete entry');
            }
        }
    }
    $out['primary_region'] = $ledger['regions']['primary'];
    $out['probe_region'] = $ledger['regions']['probe'];
    $out['modules'] = $ledger['modules'];
    $out['personnel'] = DemonstrationDocumentVersion::where('kind', 'personnel')->whereIn('region_id', array_values($ledger['regions']))->get()->toArray();
    $label = 'uiux_20261027_a01 Fictional primary office';
    if (in_array($stage, ['preflight', 'post-stop'], true)) {
        if (count($latest->offices) >= 100 || collect($latest->offices)->contains(fn ($o) => $o['label'] === $label) || count($out['personnel'])) {
            throw new RuntimeException('Office/personnel conflict');
        }
        $preview = $p->query('SELECT id,module_id,form_version_id,requirement_set_version_id,configuration_review_approval_id,channel FROM publication_manifests WHERE id=128')->fetch();
        if ($preview['channel'] !== 'review_sandbox' || (int) $preview['form_version_id'] !== 466 || $p->query('SELECT count(*) FROM publication_manifest_retirements WHERE publication_manifest_id=128')->fetchColumn() !== 0) {
            throw new RuntimeException('Preview changed');
        }
        $published = $p->query("SELECT id FROM demonstration_document_versions WHERE kind='personnel' AND state='published' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        if (array_map('intval', $published) !== [2, 4, 6, 8, 12, 14, 16, 19, 22, 25, 28, 31, 40, 42, 44, 46]) {
            throw new RuntimeException('Personnel list changed');
        }
    } else {
        $before = json_decode(file_get_contents($dir.'/preflight.json'), true, flags: JSON_THROW_ON_ERROR);
        $entries = $latest->offices;
        foreach ($before['directory']['offices'] as $key => $office) {
            if (($entries[$key] ?? null) !== $office) {
                throw new RuntimeException('Existing office changed');
            }unset($entries[$key]);
        }
        if (count($entries) !== 1) {
            throw new RuntimeException('Expected exactly one appended office');
        }
        if ($latest->created_by !== $ledger['accounts']['osds']['id']) {
            throw new RuntimeException('Unexpected directory author');
        }
        $office = array_values($entries)[0];
        $out['office_key'] = array_key_first($entries);
        if ($office !== ['label' => $label, 'director' => 'Fictional OSDS Primary Director', 'designation' => 'Synthetic demonstration director']) {
            throw new RuntimeException('Fictional office mismatch');
        }
        if ($p->query('SELECT count(*) FROM demo_office_directory_revisions')->fetchColumn() !== count($before['protected']['demo_office_directory_revisions']) + 1) {
            throw new RuntimeException('Directory revision count mismatch');
        }
        foreach ($before['counts'] as $table => $count) {
            if ($out['counts'][$table] !== $count) {
                throw new RuntimeException('Unexpected business/permission write: '.$table);
            }
        }
        $expected = $stage === 'directory' ? 0 : 2;
        if (count($out['personnel']) !== $expected) {
            throw new RuntimeException('Personnel count mismatch');
        }
        foreach ($out['personnel'] as $v) {
            if ($v['region_id'] !== $ledger['regions']['primary'] || $v['directory_revision_id'] !== $latest->id || $v['content']['office_key'] !== $out['office_key'] || $v['content']['office'] !== $office || $v['state'] !== ($stage === 'published' ? 'published' : 'draft')) {
                throw new RuntimeException('Personnel binding/state mismatch');
            }
        }
        if ($expected && array_column($out['personnel'], 'module_id') !== array_values($ledger['modules'])) {
            throw new RuntimeException('Personnel module mismatch');
        }
    }
    $versionIds = array_column($out['personnel'], 'id');
    $out['history'] = $db->table('demonstration_document_history')->whereIn('version_id', $versionIds)->orderBy('id')->get()->all();
    if (in_array($stage, ['drafts', 'published'], true)) {
        foreach ($versionIds as $id) {
            $events = array_values(array_filter($out['history'], static fn ($e) => $e->version_id === $id));
            $expected = $stage === 'published' ? ['version_created', 'version_reviewed', 'version_published'] : ['version_created'];
            if (array_column($events, 'event') !== $expected) {
                throw new RuntimeException('Personnel history order mismatch');
            }
            foreach ($events as $event) {
                $actor = $ledger['accounts'][$event->event === 'version_created' ? 'osds' : 'config-reviewer']['id'];
                if ($event->actor_id !== $actor) {
                    throw new RuntimeException('Personnel history actor mismatch');
                }
            }
        }
    }
    $out['audit'] = $db->table('audit_events')->where(function ($q) use ($versionIds, $latest) {
        $q->where('resource_type', DemoOfficeDirectoryRevision::class)->where('resource_id', (string) $latest->id);
        if ($versionIds) {
            $q->orWhere(fn ($q) => $q->where('resource_type', DemonstrationDocumentVersion::class)->whereIn('resource_id', array_map('strval', $versionIds)));
        }
    })->get(['id', 'actor_id', 'action', 'resource_type', 'resource_id'])->all();
    $out['read_only'] = $p->query('SHOW transaction_read_only')->fetchColumn();
    $p->rollBack();
    $out['status'] = 'PASS';
} catch (Throwable $e) {
    if ($p->inTransaction()) {
        $p->rollBack();
    }$out['error'] = $e->getMessage();
}
file_put_contents($file, json_encode($out, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['stage' => $stage, 'status' => $out['status'], 'error' => $out['error'] ?? null]).PHP_EOL;
exit($out['status'] === 'PASS' ? 0 : 1);
