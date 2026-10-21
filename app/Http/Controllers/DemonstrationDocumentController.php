<?php

namespace App\Http\Controllers;

use App\Services\Documents\DemonstrationCertificateRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemonstrationDocumentController extends Controller
{
    public function issuance(Request $request, DemonstrationCertificateRenderer $renderer): Response
    {
        abort_unless($renderer->canPreview($request->user()), 403);

        return response($renderer->renderIssuance($request->all()))->withHeaders([
            'Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'; form-action 'none'",
        ]);
    }

    public function show(Request $request, string $version, string $kind, DemonstrationCertificateRenderer $renderer): Response
    {
        abort_unless($renderer->canPreview($request->user()), 403);
        abort_unless($version === DemonstrationCertificateRenderer::VERSION && in_array($kind, ['cor', 'acsa'], true), 404);

        $response = response($renderer->render($kind, $version))->withHeaders([
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'; form-action 'none'",
        ]);
        if ($request->boolean('download')) {
            $response->header('Content-Disposition', 'attachment; filename="'.$kind.'-'.$version.'-DEMONSTRATION.html"');
        }

        return $response;
    }
}
