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
        //
    })->create();
