<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        if (in_array($locale, config('app.supported_locales', ['en', 'ar']), true)) {
            session(['locale' => $locale]);
        }

        return back();
    }

    /** Switch the Filament panels' locale (kept separate from the public site). */
    public function switchPanel(string $locale): RedirectResponse
    {
        if (in_array($locale, config('app.supported_locales', ['en', 'ar']), true)) {
            session(['panel_locale' => $locale]);
        }

        return back();
    }
}
