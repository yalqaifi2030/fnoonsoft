<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale for the Filament panels. Kept on a separate session key from the
 * public site so admins can run the dashboard in Arabic (RTL) while the public
 * site stays English, or vice-versa. Filament derives <html dir> from the
 * active locale (ar → rtl) and uses its bundled Arabic translations.
 */
class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'ar']);

        // Panel default is Arabic (RTL); overridden by the in-panel switcher.
        $locale = session('panel_locale', 'ar');

        if (! in_array($locale, $supported, true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
