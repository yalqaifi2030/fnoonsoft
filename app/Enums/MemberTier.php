<?php

namespace App\Enums;

/**
 * Member tiers — each grants a coloured verification badge and its own storage
 * quota + per-file size. "Free" has no badge and uses the global defaults.
 */
enum MemberTier: string
{
    case Free = 'free';
    case Bronze = 'bronze';
    case Silver = 'silver';   // yellow badge
    case Gold = 'gold';
    case Premium = 'premium'; // blue verified badge

    public function label(): string
    {
        return __('tier.'.$this->value);
    }

    /** Storage quota in GB; null = use the global default (member_quota_gb). */
    public function quotaGb(): ?float
    {
        return match ($this) {
            self::Free => null,
            self::Bronze => 25,
            self::Silver => 50,
            self::Gold => 100,
            self::Premium => 500,
        };
    }

    /** Max single-file size in GB; null = use the global default. */
    public function maxFileGb(): ?float
    {
        return match ($this) {
            self::Free => null,
            self::Bronze => 5,
            self::Silver => 10,
            self::Gold => 20,
            self::Premium => 30,
        };
    }

    public function hasBadge(): bool
    {
        return $this !== self::Free;
    }

    /** Badge colour (hex). */
    public function color(): string
    {
        return match ($this) {
            self::Free => '#9ca3af',
            self::Bronze => '#cd7f32',
            self::Silver => '#eab308',
            self::Gold => '#C9A961',
            self::Premium => '#1d9bf0',
        };
    }

    /** Badge icon (Font Awesome). */
    public function icon(): string
    {
        return 'fa-solid fa-circle-check';
    }

    /** Filament colour key for table badges. */
    public function filamentColor(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Bronze => 'warning',
            self::Silver => 'warning',
            self::Gold => 'gold',
            self::Premium => 'info',
        };
    }

    /** @return array<string, string> value => label, for selects. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $t) {
            $out[$t->value] = $t->label();
        }

        return $out;
    }
}
