<?php

use App\Http\Controllers\ApplicantAuthController;
use App\Http\Controllers\ApplicantPortalController;
use App\Http\Controllers\EvidenceLinkController;
use App\Http\Controllers\SandboxQuestionnaireController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Livewire\Applicant\DraftEditor;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', EnsureAccountIsActive::class, 'can:questionnaires.preview.sandbox'])
    ->get('/review-sandbox/questionnaires/{manifest:public_id}', [SandboxQuestionnaireController::class, 'show'])
    ->name('questionnaires.sandbox.show');

Route::get('/applicant/login', [ApplicantAuthController::class, 'create'])->name('applicant.login');
Route::post('/applicant/login', [ApplicantAuthController::class, 'store'])->name('applicant.login.store');
Route::middleware(['auth', EnsureAccountIsActive::class])->prefix('applicant')->name('applicant.')->group(function () {
    Route::post('/logout', [ApplicantAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [ApplicantPortalController::class, 'home'])->name('home');
    Route::get('/start', [ApplicantPortalController::class, 'start'])->name('start');
    Route::post('/drafts', [ApplicantPortalController::class, 'createDraft'])->name('drafts.create');
    Route::get('/drafts/{draft:public_id}', DraftEditor::class)->name('drafts.edit');
    Route::get('/evidence/{reference:public_id}/open', [EvidenceLinkController::class, 'own'])->name('evidence.open');
    Route::get('/account', [ApplicantPortalController::class, 'account'])->name('account');
});

Route::middleware(['auth', EnsureAccountIsActive::class])->get('/admin/test-reviews/{case:public_id}/evidence/{revision:public_id}/open', [EvidenceLinkController::class, 'review'])->name('evidence.review.open');
