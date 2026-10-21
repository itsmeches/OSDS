<?php

use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

// Local disposable fault server, never registered by application providers.
if (PHP_SAPI !== 'cli-server' || (int) ($_SERVER['SERVER_PORT'] ?? 0) !== 8029) {
    http_response_code(503);
    exit('Isolated atomicity test server required.');
}
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8029', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    http_response_code(503);
    exit('Uncached isolated configuration required.');
}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__.'/../public'.$path)) {
    return false;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'osds_ui_browser_test') {
    http_response_code(503);
    exit('Browser database required.');
}
if (($_SERVER['HTTP_X_ISOLATED_ATOMICITY_FAULT'] ?? '') === 'after-audit') {
    $app->bind(AuditRecorder::class, fn () => new class extends AuditRecorder
    {
        public function record(string $action, string $outcome, ?User $actor = null, ?Model $resource = null, ?int $regionId = null, ?int $institutionId = null, array $metadata = [], ?string $correlationId = null): AuditEvent
        {
            $audit = parent::record($action, $outcome, $actor, $resource, $regionId, $institutionId, $metadata, $correlationId);
            if ($action === Institution::class.'.created') {
                throw new RuntimeException('ISOLATED_K3_CREATE_AUDIT_FAILURE');
            }

            return $audit;
        }
    });
}
$app->handleRequest(Request::capture());
