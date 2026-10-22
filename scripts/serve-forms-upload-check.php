<?php

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
// Keep the normal reloading Artisan server, including its Windows environment
// filtering. Preserve only the isolated fixture settings in addition to defaults.
ServeCommand::$passthroughVariables = array_values(array_unique([
    ...ServeCommand::$passthroughVariables, 'DB_DATABASE', 'DB_URL', 'CACHE_STORE', 'APP_DEBUG', 'APP_URL', 'SESSION_COOKIE',
]));
Artisan::call('serve', ['--host' => '127.0.0.1', '--port' => '8035']);
