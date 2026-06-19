<?php

namespace App\Support;

class Geo
{
    /** Emoji flag for a 2-letter ISO country code (no images needed). */
    public static function flag(?string $cc): string
    {
        $cc = strtoupper(trim((string) $cc));

        if (strlen($cc) !== 2 || ! ctype_alpha($cc)) {
            return '🌐';
        }

        $a = ord($cc[0]) - 65 + 0x1F1E6;
        $b = ord($cc[1]) - 65 + 0x1F1E6;

        return mb_convert_encoding('&#'.$a.';&#'.$b.';', 'UTF-8', 'HTML-ENTITIES');
    }

    /** Localised country name from a 2-letter code (Arabic when the panel is in Arabic). */
    public static function country(?string $cc): string
    {
        $cc = strtoupper(trim((string) $cc));

        if (strlen($cc) !== 2) {
            return $cc !== '' ? $cc : '—';
        }

        if (function_exists('locale_get_display_region')) {
            $name = locale_get_display_region('-'.$cc, app()->getLocale());
            if ($name && strtoupper($name) !== $cc) {
                return $name;
            }
        }

        return $cc;
    }
}
