<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Visit;

/**
 * All-time human view counts for the standard footer pages (About, Mission,
 * Privacy, DMCA, …) — a quick "are people reading these?" panel on the
 * dashboard. Counts come from the visits table by path.
 */
class SitePagesViews extends BreakdownWidget
{
    protected static ?int $sort = 17;

    protected int|string|array $columnSpan = 1;

    public function heading(): string
    {
        return __('pages.dashboard_title');
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function rows(): array
    {
        $pages = [
            '/about' => __('pages.nav.about'),
            '/mission' => __('pages.nav.mission'),
            '/privacy' => __('pages.nav.privacy'),
            '/terms' => __('pages.nav.terms'),
            '/advertise' => __('pages.nav.advertise'),
            '/donate' => __('pages.nav.donate'),
            '/dmca' => __('pages.nav.dmca'),
            '/abuse' => __('pages.nav.abuse'),
            '/sitemap' => __('pages.nav.sitemap'),
            '/contact' => __('site.nav.contact'),
        ];

        $counts = Visit::query()
            ->where('is_bot', false)
            ->whereIn('path', array_keys($pages))
            ->selectRaw('path, count(*) as c')
            ->groupBy('path')
            ->pluck('c', 'path');

        $total = max(1, (int) $counts->sum());

        $rows = [];
        foreach ($pages as $path => $label) {
            $c = (int) ($counts[$path] ?? 0);
            $rows[] = ['label' => $label, 'sub' => $path, 'count' => $c, 'pct' => (int) round($c / $total * 100)];
        }

        usort($rows, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $rows;
    }
}
