<?php

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli-server' || (int) ($_SERVER['SERVER_PORT'] ?? 0) !== 8039) {
    http_response_code(503);
    exit('Isolated B3 server required');
}
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8039', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_COOKIE' => 'b3_browser_session', 'DEMO_PDF_NODE' => sys_get_temp_dir().'/osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe'] as $k => $v) {
    putenv($k.'='.$v);
    $_ENV[$k] = $_SERVER[$k] = $v;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    http_response_code(503);
    exit('Uncached isolated configuration required');
}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__.'/../public'.$path)) {
    return false;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    http_response_code(503);
    exit('Approved isolated database required');
}
if (($_SERVER['HTTP_X_ISOLATED_B3_FAULT'] ?? '') === 'after-audit') {
    $app->bind(AuditRecorder::class, fn () => new class extends AuditRecorder
    {
        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $row = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === 'demonstration_pdf.generation_completed') {
                throw new RuntimeException('PRIVATE_B3_BROWSER_FAULT');
            }

            return $row;
        }
    });
}
$app->handleRequest(Request::capture());
