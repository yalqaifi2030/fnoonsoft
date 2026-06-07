<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale (session → browser → default) for every web
 * request. The chosen locale also drives <html dir> (rtl for ar, else ltr).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'ar']);

        $locale = session('locale')
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
