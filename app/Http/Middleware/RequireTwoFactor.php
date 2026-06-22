<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After password login, force users who have confirmed two-factor auth to pass
 * a code challenge before they can use any panel. Added to each panel's
 * authMiddleware. The challenge lives on a standalone web route (outside the
 * panels) so it isn't gated by this middleware; logout is always allowed.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && method_exists($user, 'hasTwoFactorEnabled')
            && $user->hasTwoFactorEnabled()
            && ! $request->session()->get('2fa_passed')
            && ! $this->isLogout($request)) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }

    private function isLogout(Request $request): bool
    {
        $name = (string) optional($request->route())->getName();

        return str_contains($name, '.auth.logout') || $request->is('*/logout');
    }
}
