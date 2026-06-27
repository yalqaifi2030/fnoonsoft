<?php

namespace App\Support;

use App\Models\Setting;
use Filament\Support\Colors\Color;

/**
 * Central brand-colour engine. Three admin-managed colours (primary, secondary,
 * accent) drive both the public site and the Filament panels:
 *  - The public layout and panel chrome inject cssRoot() as CSS custom properties
 *    (hex for direct use + "r g b" channels for Tailwind's rgb(var() / <alpha>)).
 *  - The panels read panelColors() for their semantic palette.
 * Every getter is guarded so things still boot before the settings table exists.
 */
class Theme
{
    /** Current brand defaults (Saudi green · royal gold · bronze). */
    public const DEFAULTS = [
        'primary' => '#006C35',
        'secondary' => '#C9A961',
        'accent' => '#8B6F47',
    ];

    /**
     * Curated one-click palettes: [primary, secondary, accent].
     *
     * @var array<string,array{0:string,1:string,2:string}>
     */
    public const PRESETS = [
        'saudi' => ['#006C35', '#C9A961', '#8B6F47'],
        'emerald' => ['#0E7C66', '#E0B84C', '#1F6F78'],
        'royal' => ['#1E3A8A', '#C9A961', '#7C3AED'],
        'sunset' => ['#C2410C', '#F59E0B', '#9D174D'],
        'midnight' => ['#0B1F3A', '#38BDF8', '#22D3EE'],
        'rose' => ['#9F1239', '#D4AF37', '#7E22CE'],
        'forest' => ['#14532D', '#A3B18A', '#5E503F'],
        'mono' => ['#1F2937', '#9CA3AF', '#4B5563'],
        // Classic, luxurious palettes that read as "premium / powerful".
        'obsidian' => ['#1A1A1A', '#C9A961', '#8B6F47'],
        'burgundy' => ['#6D1A36', '#C9A961', '#3E1228'],
        'imperial' => ['#13294B', '#CBA135', '#1E3A5F'],
        'noir_emerald' => ['#064E3B', '#D4AF37', '#0F766E'],
        'espresso' => ['#3E2723', '#C9A227', '#8D6E63'],
        'sapphire' => ['#1E3A5F', '#B08D57', '#2E5984'],
    ];

    private static function read(string $key, string $default): string
    {
        try {
            $v = Setting::get($key);

            return self::valid(is_string($v) ? $v : null) ? $v : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function valid(?string $hex): bool
    {
        return is_string($hex) && (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $hex);
    }

    public static function primary(): string
    {
        return self::read('theme_primary', self::DEFAULTS['primary']);
    }

    public static function secondary(): string
    {
        return self::read('theme_secondary', self::DEFAULTS['secondary']);
    }

    public static function accent(): string
    {
        return self::read('theme_accent', self::DEFAULTS['accent']);
    }

    /** Apply the brand colours to the Filament panels too? */
    public static function appliesToPanel(): bool
    {
        try {
            return (bool) Setting::get('theme_apply_panel', true);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** Filament semantic palette for a panel's ->colors(). */
    public static function panelColors(): array
    {
        if (! self::appliesToPanel()) {
            return [
                'primary' => Color::hex(self::DEFAULTS['primary']),
                'gold' => Color::hex(self::DEFAULTS['secondary']),
            ];
        }

        return [
            'primary' => Color::hex(self::primary()),
            'gold' => Color::hex(self::secondary()),
            'accent' => Color::hex(self::accent()),
        ];
    }

    /** @return array{0:int,1:int,2:int} */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /** "r g b" channels — for rgb(var(--x) / <alpha-value>). */
    public static function rgb(string $hex): string
    {
        return implode(' ', self::toRgb($hex));
    }

    /** Darken a hex toward black by $pct (0..1). */
    public static function darken(string $hex, float $pct = 0.15): string
    {
        [$r, $g, $b] = self::toRgb($hex);
        $f = max(0.0, 1 - $pct);

        return sprintf('#%02x%02x%02x', (int) round($r * $f), (int) round($g * $f), (int) round($b * $f));
    }

    /** The :root{} declaration injected into the site + panel <head>. */
    public static function cssRoot(): string
    {
        $p = self::primary();
        $s = self::secondary();
        $a = self::accent();

        $vars = [
            '--color-primary' => $p,
            '--color-primary-dark' => self::darken($p, 0.18),
            '--color-primary-darker' => self::darken($p, 0.62),
            '--color-secondary' => $s,
            '--color-secondary-dark' => self::darken($s, 0.18),
            '--color-accent' => $a,
            '--color-dark' => '#1A1A1A',
            '--c-primary' => self::rgb($p),
            '--c-secondary' => self::rgb($s),
            '--c-accent' => self::rgb($a),
        ];

        $body = '';
        foreach ($vars as $k => $v) {
            $body .= $k.':'.$v.';';
        }

        return ':root{'.$body.'}';
    }
}
