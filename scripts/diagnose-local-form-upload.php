<?php

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.connections.pgsql.database') !== 'osds_dev') {
    throw new RuntimeException('Local upload diagnostic only.');
}
// Exercise only the normal signed temporary-upload boundary; no account login,
// catalogue publication, fixture seeding or business database mutation.
$cookies = new CookieJar;
$login = Http::withOptions(['cookies' => $cookies])->get('http://localhost:8000/admin/login');
if (! preg_match('/name="csrf-token"\s+content="([^"]+)"/', $login->body(), $match)) {
    throw new RuntimeException('Could not obtain the normal login-page CSRF token.');
}
app('url')->forceRootUrl('http://localhost:8000');
$url = app(GenerateSignedUploadUrl::class)->forLocal();
$response = Http::withOptions(['cookies' => $cookies])->withHeaders(['X-CSRF-TOKEN' => $match[1], 'Accept' => 'application/json'])
    ->attach('files[]', fopen(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip'), 'rb'), 'forms.zip')->post($url);
file_put_contents(storage_path('app/private/local-upload-diagnostic-response.txt'), $response->body());
echo json_encode(['status' => $response->status(), 'valid_paths_response' => is_array($response->json('paths')), 'body_bytes' => strlen($response->body())], JSON_THROW_ON_ERROR);
