<?php

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
        // Behind Coolify/Traefik (TLS terminates at the proxy, forwards as HTTP).
        // Trust the X-Forwarded-* headers so Laravel knows the request is HTTPS and
        // generates https:// asset URLs (otherwise Filament CSS is blocked as mixed content).
        $middleware->trustProxies(at: '*');

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
