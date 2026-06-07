<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * "Editor's choice" spotlight on the homepage, configured from
 * Admin → Site settings → Editor's choice. All getters are guarded so the
 * homepage still renders before the settings table exists.
 */
class Spotlight
{
    private static function raw(string $key, mixed $default = null): mixed
    {
        try {
            $value = Setting::get($key);

            return filled($value) ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function enabled(): bool
    {
        return (bool) self::raw('spotlight_enabled', true);
    }

    /** Admin-picked software id, or null to auto-pick the top featured one. */
    public static function softwareId(): ?int
    {
        $v = self::raw('spotlight_software_id');

        return $v ? (int) $v : null;
    }

    /** Background image URL, or null to use the plain gradient. */
    public static function bg(): ?string
    {
        $path = self::raw('spotlight_bg');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** Dim level: soft | medium | strong. */
    public static function level(): string
    {
        $l = self::raw('spotlight_overlay', 'medium');

        return in_array($l, ['soft', 'medium', 'strong'], true) ? $l : 'medium';
    }

    /** CSS gradient used to dim the background image for text legibility. */
    public static function overlayStyle(): string
    {
        return match (self::level()) {
            'soft' => 'linear-gradient(135deg, rgba(15,42,28,.70), rgba(26,26,26,.72))',
            'strong' => 'linear-gradient(135deg, rgba(15,42,28,.95), rgba(26,26,26,.96))',
            default => 'linear-gradient(135deg, rgba(15,42,28,.86), rgba(26,26,26,.88))',
        };
    }

    /** Optional custom badge label, or null to use the translated default. */
    public static function badge(): ?string
    {
        return self::raw('spotlight_badge') ?: null;
    }
}
