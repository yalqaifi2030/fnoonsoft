<?php

namespace App\Http\Controllers;

use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The post-login two-factor challenge: enter an authenticator code (or a
 * one-time recovery code) to finish signing in.
 */
class TwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect('/');
        }
        if (TwoFactor::passed($user)) {
            return redirect()->intended('/');
        }

        return view('auth.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);

        $user = $request->user();
        $code = trim((string) $request->input('code'));

        if ($user && TwoFactor::attempt($user, $code)) {
            $request->session()->regenerate(); // fresh session id once fully signed in
            TwoFactor::markPassed($user);

            return redirect()->intended('/dashboard');
        }

        if ($user && ($wait = TwoFactor::lockedFor($user)) > 0) {
            try {
                \App\Support\Security::flag($request, 'two_factor', 'medium', '2FA locked after repeated wrong codes (user #'.$user->getKey().')');
            } catch (\Throwable $e) {
                // best-effort logging
            }

            return back()->withErrors(['code' => __('auth.throttle', ['seconds' => $wait, 'minutes' => (int) ceil($wait / 60)])]);
        }

        return back()->withErrors(['code' => __('security.bad_code')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
