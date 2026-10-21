<?php

namespace App\Http\Controllers;

use App\Models\DemoIssuanceFormRevision;
use App\Models\DemoOfficeDirectoryRevision;
use App\Services\Documents\DemoIssuanceFormService;
use Illuminate\Http\Request;

class ApplicantIssuanceDemoController extends Controller
{
    public function show(Request $request, DemoIssuanceFormService $service)
    {
        abort_unless($service->canAnswer($request->user()), 403);

        return response()->view('applicant.issuance-demo', [
            'form' => DemoIssuanceFormRevision::latest('id')->first(),
            'directory' => DemoOfficeDirectoryRevision::latest('id')->first(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function preview(Request $request, DemoIssuanceFormService $service)
    {
        abort_unless($service->canAnswer($request->user()), 403);

        return response($service->renderApplicant($request->all()))->withHeaders([
            'Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'self'; form-action 'none'",
        ]);
    }
}
