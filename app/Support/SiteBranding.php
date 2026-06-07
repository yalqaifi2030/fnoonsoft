<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Public-site visual identity (logo + favicon) configured from
 * Admin → Site settings → Visual identity. Guarded so views still render
 * before the settings table exists.
 */
class SiteBranding
{
    private static function url(string $key): ?string
    {
        try {
            $path = Setting::get($key);

            return filled($path) ? Storage::disk('public')->url($path) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Public site logo URL, or null to fall back to the icon + name mark. */
    public static function logo(): ?string
    {
        return self::url('site_logo');
    }

    /** Favicon URL, or null for the framework default. */
    public static function favicon(): ?string
    {
        return self::url('site_favicon');
    }
}
