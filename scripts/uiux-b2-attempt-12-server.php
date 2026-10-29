<?php

use Illuminate\Http\Request;

if (PHP_SAPI !== 'cli-server' || (int) ($_SERVER['SERVER_PORT'] ?? 0) !== 8044) {
    http_response_code(503);
    exit;
}
require __DIR__.'/UiUxB2Attempt12Bootstrap.php';
$path = realpath($root.'/public'.parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$public = realpath($root.'/public');
if ($path !== false && str_starts_with($path, $public.DIRECTORY_SEPARATOR) && is_file($path)) {
    return false;
}
$app->handleRequest(Request::capture());
