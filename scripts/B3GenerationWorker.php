<?php

use App\Models\User;
use App\Services\Documents\DemonstrationDocumentService;
use App\Services\Documents\PrivateDemonstrationPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (DB::connection()->getDatabaseName() !== 'osds_ui_browser_test') {
    throw new RuntimeException('Approved isolated database required');
}
config(['demonstration_documents.node' => sys_get_temp_dir().'/osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe']);
$data = json_decode(file_get_contents(storage_path('app/private/b3-scenario.json')), true, flags: JSON_THROW_ON_ERROR);
$mode = $argv[1] ?? '';
$run = $argv[2] ?? '';
if (! preg_match('/^[a-f0-9-]{36}$/', $run)) {
    throw new RuntimeException('Invalid run identifier');
}
$barrier = storage_path('app/private/b3-'.$run);
if ($mode === 'A') {
    app()->bind(PrivateDemonstrationPdf::class, fn () => new class($barrier) extends PrivateDemonstrationPdf
    {
        public function __construct(private string $barrier) {}

        public function render(string $html, string $directory): string
        {
            file_put_contents($this->barrier.'-ready', json_encode(['pid' => DB::selectOne('select pg_backend_pid() as pid')->pid, 'transactionDepth' => DB::transactionLevel(), 'phase' => 'rendering_outside_transaction']));
            $end = microtime(true) + 35;
            while (! is_file($this->barrier.'-release')) {
                if (microtime(true) > $end) {
                    throw new RuntimeException('Harness release timeout');
                }usleep(30000);
            }

            return parent::render($html, $directory);
        }
    });
}
$actor = User::findOrFail($data['operator']['id']);
auth()->login($actor);
$pid = DB::selectOne('select pg_backend_pid() as pid')->pid;
try {
    $d = app(DemonstrationDocumentService::class)->generate($actor, $data['request'], $data['template'], $data['personnel']);
    echo json_encode(['pid' => $pid, 'outcome' => 'completed', 'id' => $d->id]);
} catch (AuthorizationException) {
    echo json_encode(['pid' => $pid, 'outcome' => 'denied']);
} catch (ValidationException) {
    echo json_encode(['pid' => $pid, 'outcome' => 'rejected']);
}
