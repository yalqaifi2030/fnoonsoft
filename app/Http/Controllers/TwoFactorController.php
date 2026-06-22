<?php

namespace App\Http\Controllers;

use App\Support\Totp;
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
        if ($request->session()->get('2fa_passed')) {
            return redirect()->intended('/');
        }

        return view('auth.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);

        $user = $request->user();
        $code = trim((string) $request->input('code'));

        if ($user && $this->passes($user, $code)) {
            $request->session()->put('2fa_passed', true);

            return redirect()->intended('/dashboard');
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

    private function passes($user, string $code): bool
    {
        if ($user->two_factor_secret && Totp::verify($user->two_factor_secret, $code)) {
            return true;
        }

        // Recovery codes are stored upper-cased.
        return $user->useRecoveryCode(strtoupper($code));
    }
}
