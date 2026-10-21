<?php

namespace App\Http\Controllers;

use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Documents\DemoIssuanceFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantAuthController
{
    public function create(ApplicantSandboxGuard $guard)
    {
        abort_unless($guard->allowed() || app(DemoIssuanceFormService::class)->available(), 404);

        return view('applicant.login', ['applicationSandbox' => $guard->allowed()]);
    }

    public function store(Request $request, ApplicantSandboxGuard $guard): RedirectResponse
    {
        $applicationSandbox = $guard->allowed();
        abort_unless($applicationSandbox || app(DemoIssuanceFormService::class)->available(), 404);
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The credentials did not match an active applicant account.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user = auth()->user();
        if (! $user->isActive()) {
            Auth::logout();

            return back()->withErrors(['email' => $user->status === 'pending_verification'
                ? 'This SCHOOL account is awaiting verification by another authorized CHEDRO reviewer.'
                : 'This applicant account is not active.'])->onlyInput('email');
        }
        if (! $user->can('applicant.portal.access') || ($applicationSandbox && ! $user->is_synthetic)) {
            Auth::logout();

            return back()->withErrors(['email' => $applicationSandbox
                ? 'This account is not enabled for the synthetic applicant sandbox.'
                : 'This account is not enabled for applicant resources.'])->onlyInput('email');
        }

        return redirect()->route($applicationSandbox ? 'applicant.home' : 'applicant.issuance-demo');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('applicant.login');
    }
}
