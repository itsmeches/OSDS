<?php

putenv('APP_ENV=sandbox');
putenv('DB_DATABASE=osds_sandbox');
$_ENV['APP_ENV'] = 'sandbox';
$_ENV['DB_DATABASE'] = 'osds_sandbox';
$_SERVER['APP_ENV'] = 'sandbox';
$_SERVER['DB_DATABASE'] = 'osds_sandbox';

chdir(__DIR__.'/../public');

// Static assets must be returned by this outer router. A `return false` from
// Laravel's included router does not propagate to PHP's built-in server.
$requestPath = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($requestPath !== '/' && is_file(getcwd().$requestPath)) {
    return false;
}

require __DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
