<?php

use App\Http\Controllers\SandboxQuestionnaireController;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', EnsureAccountIsActive::class, 'can:questionnaires.preview.sandbox'])
    ->get('/review-sandbox/questionnaires/{manifest:public_id}', [SandboxQuestionnaireController::class, 'show'])
    ->name('questionnaires.sandbox.show');
