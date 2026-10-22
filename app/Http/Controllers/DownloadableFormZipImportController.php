<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DownloadableForms;
use App\Services\Downloads\DownloadableFormService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class DownloadableFormZipImportController extends Controller
{
    public function store(Request $request, DownloadableFormService $service)
    {
        abort_unless($service->canManage($request->user()), 403);
        $data = $request->validate(['archive' => 'required|file|max:10240', 'archiveVersion' => 'required|string|max:80']);
        try {
            $count = $service->importZip($request->user(), $request->file('archive'), $data['archiveVersion']);
        } catch (AuthorizationException|ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $reference = (string) Str::uuid();
            Log::error('ZIP import could not be confirmed', ['support_reference' => $reference, 'exception_class' => $exception::class]);

            return redirect(DownloadableForms::getUrl())->withInput($request->only('archiveVersion'))
                ->withErrors(['archive' => 'Import could not be confirmed. Inspect the catalogue before trying again. Support reference: '.$reference]);
        }

        return redirect(DownloadableForms::getUrl())->with('zip_import_success', $count.' forms imported');
    }
}
