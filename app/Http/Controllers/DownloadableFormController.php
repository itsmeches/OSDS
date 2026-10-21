<?php

namespace App\Http\Controllers;

use App\Models\DownloadableForm;
use App\Services\Downloads\DownloadableFormService;
use Illuminate\Support\Facades\Storage;

class DownloadableFormController extends Controller
{
    public function index(DownloadableFormService $service)
    {
        abort_unless($service->canRead(auth()->user()) && auth()->user()->fresh()->can('applicant.portal.access'), 403);

        return view('applicant.downloadable-forms', ['forms' => DownloadableForm::with(['currentVersion', 'module'])
            ->whereNull('withdrawn_at')->whereNotNull('current_version_id')->orderBy('title')->orderBy('id')->paginate(10)]);
    }

    public function download(DownloadableForm $form, int $version, DownloadableFormService $service)
    {
        abort_unless($service->canRead(auth()->user()), 404);
        // The URL binds the version shown in the catalogue. A replacement or
        // withdrawal invalidates the old URL rather than returning other bytes.
        abort_if($form->withdrawn_at || $form->current_version_id !== $version, 404);
        $file = $form->currentVersion;
        abort_unless($file && Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
