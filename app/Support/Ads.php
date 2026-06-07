<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Google AdSense integration, configured from Admin → Site settings → Ads.
 * All getters are guarded so the public site renders before the settings table
 * exists. `enabled()` is the single gate for both the loader script and units.
 */
class Ads
{
    /** Placements that have a manual ad slot. */
    public const PLACEMENTS = ['header', 'incontent', 'sidebar', 'gateway', 'footer'];

    private function setting(string $key, mixed $default = null): mixed
    {
        try {
            $value = Setting::get($key);

            return filled($value) ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /** Master switch: on, has a publisher id, and not hidden from the current (member) viewer. */
    public function enabled(): bool
    {
        if (! (bool) $this->setting('ads_enabled', false)) {
            return false;
        }
        if (! $this->publisherId()) {
            return false;
        }
        if ((bool) $this->setting('ads_hide_members', false) && auth()->check()) {
            return false;
        }

        return true;
    }

    public function publisherId(): ?string
    {
        $id = $this->setting('ads_publisher_id');

        return $id ? trim((string) $id) : null;
    }

    /** 'auto' (Google places ads) or 'manual' (our <x-ad> units). */
    public function mode(): string
    {
        return $this->setting('ads_mode', 'auto') === 'manual' ? 'manual' : 'auto';
    }

    public function slot(string $placement): ?string
    {
        $slot = $this->setting('ads_slot_'.$placement);

        return $slot ? trim((string) $slot) : null;
    }

    /** Render a manual unit only in manual mode with a configured slot. */
    public function showUnit(string $placement): bool
    {
        return $this->enabled() && $this->mode() === 'manual' && filled($this->slot($placement));
    }

    /** The required /ads.txt line, derived from the publisher id. */
    public function adsTxt(): ?string
    {
        $pub = $this->publisherId();
        if (! $pub) {
            return null;
        }

        // ca-pub-1234567890 → pub-1234567890
        $id = str_starts_with($pub, 'ca-') ? substr($pub, 3) : $pub;

        return "google.com, {$id}, DIRECT, f08c47fec0942fa0";
    }
}
