<?php

namespace App\Support;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;

/**
 * Central "about this site" facts: the site version, the (mostly dynamic) tech
 * stack, and the developer credit — surfaced on the admin About page.
 */
class SiteInfo
{
    /** Bump this on a meaningful release. Overridable via APP_VERSION in .env. */
    public const VERSION = '2.5.0';

    public const RELEASED = '2026-07-30';

    public const CODENAME = 'Fortress';

    public static function version(): string
    {
        return (string) (env('APP_VERSION') ?: self::VERSION);
    }

    /** Developer credit (matches the public footer). */
    public static function developer(): array
    {
        return [
            'name' => 'ياسر القيفي',
            'name_en' => 'Yasser Al-Qaifi',
            'website' => 'https://applms.net',
            'phone' => '0555299782',
        ];
    }

    /**
     * The tech stack, grouped, with versions resolved live where possible.
     *
     * @return array<string, array{icon:string, color:string, label:string, items:array<int,array{name:string, value:string}>}>
     */
    public static function stack(): array
    {
        return [
            'backend' => [
                'icon' => 'fa-solid fa-server', 'color' => '#dc2626', 'label' => __('about.group.backend'),
                'items' => [
                    ['name' => 'Laravel', 'value' => app()->version()],
                    ['name' => 'PHP', 'value' => PHP_VERSION],
                    ['name' => 'MySQL', 'value' => self::mysqlVersion()],
                    ['name' => __('about.tech.db'), 'value' => strtoupper((string) config('database.default'))],
                ],
            ],
            'admin' => [
                'icon' => 'fa-solid fa-gauge-high', 'color' => '#7c3aed', 'label' => __('about.group.admin'),
                'items' => [
                    ['name' => 'Filament', 'value' => self::pkg('filament/filament')],
                    ['name' => 'Livewire', 'value' => self::pkg('livewire/livewire')],
                    ['name' => 'Alpine.js', 'value' => 'v3'],
                    ['name' => __('about.tech.panels'), 'value' => '/admin · /upload · /dashboard'],
                ],
            ],
            'frontend' => [
                'icon' => 'fa-solid fa-palette', 'color' => '#0891b2', 'label' => __('about.group.frontend'),
                'items' => [
                    ['name' => 'Tailwind CSS', 'value' => 'v3'],
                    ['name' => 'Alpine.js', 'value' => 'v3'],
                    ['name' => 'Font Awesome', 'value' => '6.5'],
                    ['name' => __('about.tech.i18n'), 'value' => 'ar / en · RTL'],
                ],
            ],
            'infra' => [
                'icon' => 'fa-solid fa-shield-halved', 'color' => '#16a34a', 'label' => __('about.group.infra'),
                'items' => [
                    ['name' => __('about.tech.server'), 'value' => 'Nginx · aaPanel (VPS)'],
                    ['name' => __('about.tech.cdn'), 'value' => 'Cloudflare'],
                    ['name' => __('about.tech.storage'), 'value' => 'iDrive e2 (S3) · '.__('about.tech.storage_note')],
                    ['name' => __('about.tech.cache'), 'value' => ucfirst((string) config('cache.default')).' / '.ucfirst((string) config('queue.default'))],
                ],
            ],
        ];
    }

    /** Composer package pretty version, gracefully degrading. */
    public static function pkg(string $name): string
    {
        try {
            if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($name)) {
                return (string) InstalledVersions::getPrettyVersion($name);
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return '—';
    }

    private static function mysqlVersion(): string
    {
        try {
            $v = DB::selectOne('select version() as v');

            // "8.0.36-0ubuntu..." → "8.0.36"
            return preg_replace('/[-+].*$/', '', (string) ($v->v ?? '')) ?: '—';
        } catch (\Throwable $e) {
            return '—';
        }
    }
}
