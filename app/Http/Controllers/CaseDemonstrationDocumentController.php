<?php

namespace App\Http\Controllers;

use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Services\Documents\DemonstrationDocumentService;
use App\Services\Documents\PrivateDemonstrationPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CaseDemonstrationDocumentController extends Controller
{
    public function download(Request $request, string $document)
    {
        $doc = DemonstrationDocument::where('public_id', $document)->firstOrFail();
        $bytes = app(DemonstrationDocumentService::class)->verifiedBytes($request->user(), $doc);

        return response($bytes)->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$doc->certificate_type.'-'.$doc->public_id.'-DEMONSTRATION.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function preview(Request $request, int $version)
    {
        $service = app(DemonstrationDocumentService::class);
        $html = $service->preview($request->user(), DemonstrationDocumentVersion::findOrFail($version));
        $temp = storage_path('app/private/demonstration-documents/tmp/'.Str::uuid());
        try {
            $path = app(PrivateDemonstrationPdf::class)->render($html, $temp);
            app(PrivateDemonstrationPdf::class)->inspect($path);

            return response(file_get_contents($path))->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="template-preview-DEMONSTRATION.pdf"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        } catch (\Throwable $e) {
            $reference = (string) Str::uuid();
            Log::error('Demonstration preview failed', ['reference' => $reference, 'exception_class' => $e::class]);
            throw ValidationException::withMessages(['document' => 'Preview unavailable. Support reference: '.$reference]);
        } finally {
            foreach (['input.html', 'rendered.pdf'] as $name) {
                if (is_file($temp.'/'.$name)) {
                    unlink($temp.'/'.$name);
                }
            }
            if (is_dir($temp)) {
                @rmdir($temp);
            }
        }
    }
}
