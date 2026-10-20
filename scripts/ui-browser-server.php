<?php

// Dedicated synthetic browser environment. Never reuse either manual-testing database.
foreach (['APP_ENV' => 'sandbox', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8017', 'DB_DATABASE' => 'osds_ui_browser_test', 'DB_URL' => '', 'CACHE_STORE' => 'array'] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

if (is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    http_response_code(503);
    exit('Browser verification requires uncached configuration.');
}

chdir(__DIR__.'/../public');
$requestPath = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if ($requestPath !== '/' && is_file(getcwd().$requestPath)) {
    return false;
}
require __DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
