<?php

namespace App\Support;

use App\Models\Setting;
use Filament\Enums\ThemeMode;
use Illuminate\Support\Facades\Storage;

/**
 * Reads the admin-configurable panel branding (name, logo, favicon, theme) from
 * the settings table. Every getter is guarded so panels still boot before the
 * settings table exists (fresh install / migrate).
 */
class Branding
{
    private static function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = Setting::get($key);

            return filled($value) ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /** Brand name for a panel ('admin' | 'upload'), with a sensible default. */
    public static function name(string $panel, string $default): string
    {
        return (string) self::get("brand_{$panel}_name", $default);
    }

    /** Logo: the uploaded image URL when set, otherwise the default brand view. */
    public static function logo(string $panel): mixed
    {
        $path = self::get("brand_{$panel}_logo");

        if ($path) {
            return Storage::disk('public')->url($path);
        }

        return view("filament.{$panel}.brand");
    }

    public static function logoHeight(): string
    {
        return (string) self::get('brand_logo_height', '3.5rem');
    }

    public static function favicon(): ?string
    {
        $path = self::get('brand_favicon');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function themeMode(): ThemeMode
    {
        return match (self::get('brand_theme', 'system')) {
            'light' => ThemeMode::Light,
            'dark' => ThemeMode::Dark,
            default => ThemeMode::System,
        };
    }
}
