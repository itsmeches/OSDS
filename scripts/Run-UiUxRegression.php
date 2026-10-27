<?php

declare(strict_types=1);

if ($argc !== 2 || $argv[1] !== 'targeted') {
    fwrite(STDERR, "B1 accepts only targeted; full suite is not authorized.\n");
    exit(2);
}
$root = dirname(__DIR__);
$attempt = $root.'/storage/app/private/uiux-consolidated-acceptance/gate-b1/attempt-03';
if (! mkdir($attempt, 0700)) {
    throw new RuntimeException('B1 attempt-03 already exists or cannot be reserved; no retry permitted.');
}
file_put_contents($attempt.'/started.json', json_encode(['attempt' => 'b1-attempt-03', 'started' => gmdate('c')], JSON_PRETTY_PRINT));
$files = [
    'tests/Feature/DashboardGuidanceTest.php',
    'tests/Unit/ApplicantApplicationGuidanceTest.php',
    'tests/Feature/FormMakerTest.php',
    'tests/Feature/QuestionnaireConfigurationTest.php',
    'tests/Feature/ApplicantSandboxTest.php',
    'tests/Feature/ApplicationDecisionFoundationTest.php',
    'tests/Feature/CaseIssuanceRequestTest.php',
    'tests/Feature/DemonstrationDocumentTest.php',
    'tests/Feature/DownloadableFormsTest.php',
];
foreach ($files as $file) {
    if (! is_file($root.'/'.$file)) {
        throw new RuntimeException('Missing approved test: '.$file);
    }
    if (preg_match('~docs/evidence|phase-3d7-k[1-5]~', file_get_contents($root.'/'.$file))) {
        throw new RuntimeException('Historical evidence writer detected; B4 is not authorized.');
    }
}
// Keep the original bootstrap and first attempt's identity log unchanged.
// Only relocate paths in this private snapshot; all guard logic stays identical.
$source = file_get_contents($root.'/scripts/UiUxAcceptanceBootstrap.php');
$snapshot = str_replace('dirname(__DIR__)', var_export($root, true), $source);
$snapshot = str_replace('/gate-b1/identities.jsonl', '/gate-b1/attempt-03/identities.jsonl', $snapshot);
$bootstrap = $attempt.'/bootstrap.php';
file_put_contents($bootstrap, $snapshot);
file_put_contents($attempt.'/bootstrap-provenance.json', json_encode(['source_sha256' => hash('sha256', $source), 'snapshot_sha256' => hash('sha256', $snapshot), 'changes' => ['absolute project root', 'attempt-local identity log']], JSON_PRETTY_PRINT));
$lint = proc_open([PHP_BINARY, '-l', $bootstrap], [0 => ['pipe', 'r'], 1 => ['file', $attempt.'/bootstrap-syntax.txt', 'x'], 2 => ['redirect', 1]], $lintPipes, $root);
if (! is_resource($lint)) {
    throw new RuntimeException('Cannot validate attempt bootstrap.');
}
fclose($lintPipes[0]);
if (proc_close($lint) !== 0) {
    throw new RuntimeException('Attempt bootstrap syntax failed; no database execution.');
}
$command = [PHP_BINARY, '-d', 'memory_limit=512M', $root.'/vendor/phpunit/phpunit/phpunit', '--bootstrap', $bootstrap, '--do-not-cache-result', '--stop-on-failure', '--stop-on-error', ...$files];
$log = $attempt.'/targeted.txt';
$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $log, 'x'], 2 => ['redirect', 1]], $pipes, $root);
if (! is_resource($process)) {
    throw new RuntimeException('Could not start guarded PHPUnit.');
}
fclose($pipes[0]);
$exit = proc_close($process);
file_put_contents(dirname($log).'/execution.json', json_encode(['files' => $files, 'command' => $command, 'exit' => $exit, 'finished' => gmdate('c')], JSON_PRETTY_PRINT));
echo 'Targeted PHPUnit exit '.$exit.'. Private log: '.$log.PHP_EOL;
exit($exit);
