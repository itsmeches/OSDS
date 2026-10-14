<?php

namespace App\Http\Controllers;

use App\Models\PublicationManifest;
use Illuminate\Contracts\View\View;

class SandboxQuestionnaireController extends Controller
{
    public function show(PublicationManifest $manifest): View
    {
        abort_unless($manifest->channel === 'review_sandbox' && ! $manifest->retirement()->exists(), 404);
        $manifest->load('module', 'formVersion.definition', 'formVersion.sections.questions.options', 'requirementSetVersion.requirements.providers');

        return view('questionnaires.sandbox', compact('manifest'));
    }
}
