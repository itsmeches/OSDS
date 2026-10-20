<?php

namespace App\Http\Controllers;

use App\Services\Applicants\ApplicantSandboxGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantAuthController
{
    public function create(ApplicantSandboxGuard $guard)
    {
        abort_unless($guard->allowed(), 404);

        return view('applicant.login');
    }

    public function store(Request $request, ApplicantSandboxGuard $guard): RedirectResponse
    {
        abort_unless($guard->allowed(), 404);
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
        if (! $user->is_synthetic || ! $user->can('applicant.portal.access')) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is not enabled for the synthetic applicant sandbox.'])->onlyInput('email');
        }

        return redirect()->route('applicant.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('applicant.login');
    }
}
