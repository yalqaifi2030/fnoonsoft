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

        return $this->backHere();
    }

    /** Switch the Filament panels' locale (kept separate from the public site). */
    public function switchPanel(string $locale): RedirectResponse
    {
        if (in_array($locale, config('app.supported_locales', ['en', 'ar']), true)) {
            session(['panel_locale' => $locale]);
        }

        return $this->backHere();
    }

    /** Back to the previous page — but only if it is on THIS site (Referer is attacker-controllable). */
    private function backHere(): RedirectResponse
    {
        $previous = url()->previous();

        return parse_url($previous, PHP_URL_HOST) === request()->getHost()
            ? redirect()->to($previous)
            : redirect()->route('home');
    }
}
