<?php

use App\Http\Middleware\EnsureSiteIsAvailable;
use App\Http\Middleware\SetLocale;
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
        ]);

        // Local-disk chunk uploads are authenticated by a signed URL, not a CSRF
        // token (Uppy PUTs raw bytes with no token), so exempt that one endpoint.
        $middleware->validateCsrfTokens(except: [
            'upload/multipart/put-part/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Any invalid public URL (a 404) sends the visitor to the home page
        // instead of an error page. APIs/JSON, Livewire and the Filament panels
        // keep their own 404 handling so they aren't disrupted.
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()
                || $request->is('api/*', 'livewire/*', 'admin', 'admin/*', 'upload', 'upload/*', 'dashboard', 'dashboard/*')) {
                return null;
            }

            return redirect()->route('home');
        });
    })->create();
