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
        } $request->session()->regenerate();
        if (! auth()->user()->isActive() || ! auth()->user()->is_synthetic || ! auth()->user()->can('applicant.portal.access')) {
            Auth::logout();
            abort(403);
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
