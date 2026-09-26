<?php

use App\Http\Middleware\EnforceOrigin;
use App\Http\Middleware\EnsureSiteIsAvailable;
use App\Http\Middleware\SecurityGuard;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind Cloudflare (and locally a Docker/Traefik proxy). Trust X-Forwarded-*
        // ONLY from those peers, and never X-Forwarded-Host: Cloudflare forwards a
        // client-supplied one, which poisoned generated URLs (password-reset links).
        $middleware->trustProxies(
            at: \App\Support\TrustedProxies::all(),
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        // Origin protection runs FIRST and GLOBALLY (every route incl. the
        // Filament /admin + /upload panels — the panels don't use the 'web'
        // group alias) — reject raw-IP / off-domain access, and, when enabled,
        // anything that didn't come through Cloudflare.
        $middleware->prepend(EnforceOrigin::class);

        // Two-factor gate for authenticated routes that live outside the panels.
        $middleware->alias(['two-factor' => \App\Http\Middleware\RequireTwoFactor::class]);

        // There is no global "login" route (each panel has its own) — guests hitting
        // an auth-only web route went to a 500 "Route [login] not defined".
        $middleware->redirectGuestsTo(fn () => route('home'));

        $middleware->web(append: [
            SetLocale::class,
            EnsureSiteIsAvailable::class,
            TrackVisit::class,
            // App-layer intrusion detection + auto-block (after session so events
            // can be tied to a signed-in member; staff are exempted inside).
            SecurityGuard::class,
        ]);

        // Security hardening headers on every response (UpGuard findings).
        $middleware->append(SecurityHeaders::class);

        // Local-disk chunk uploads are authenticated by a signed URL, not a CSRF
        // token (Uppy PUTs raw bytes with no token), so exempt that one endpoint.
        $middleware->validateCsrfTokens(except: [
            'upload/multipart/put-part/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Friendly handling for 403/404 on normal (HTML) requests:
        //  - 403: an authenticated user hit a panel they can't access → send
        //    them to the panel they CAN use, with a notice (no raw "Forbidden").
        //  - 404: an invalid public URL → home page.
        // API/JSON and Livewire keep their default handling.
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e,
            \Illuminate\Http\Request $request
        ) {
            $status = $e->getStatusCode();

            if ($request->expectsJson()
                || $request->is('api/*', 'livewire/*')
                || ! in_array($status, [403, 404], true)) {
                return null;
            }

            if ($status === 403) {
                $user = $request->user();
                $target = $user ? ($user->isStaff() ? '/admin' : '/dashboard') : route('home');

                try {
                    \Filament\Notifications\Notification::make()
                        ->title(__('site.forbidden'))
                        ->danger()
                        ->send();
                } catch (\Throwable $ex) {
                    // notification is best-effort; never block the redirect
                }

                return redirect($target);
            }

            // 404 — panels keep their own 404 page; the public site goes home.
            if ($request->is('admin', 'admin/*', 'upload', 'upload/*', 'dashboard', 'dashboard/*')) {
                return null;
            }

            return redirect()->route('home');
        });
    })->create();
