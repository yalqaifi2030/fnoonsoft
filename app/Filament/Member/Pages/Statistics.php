<?php

namespace App\Filament\Member\Pages;

use App\Models\Asset;
use Filament\Pages\Page;

/**
 * The member's analytics page: headline counters, a 14-day upload sparkline,
 * top files by downloads, a by-type breakdown, and an activity rank — all
 * scoped to the signed-in member.
 */
class Statistics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.member.statistics';

    public static function getNavigationLabel(): string
    {
        return __('member.stats.nav');
    }

    public function getTitle(): string
    {
        return __('member.stats.title');
    }

    protected function getViewData(): array
    {
        $uid = auth()->id();
        $base = fn () => Asset::where('user_id', $uid);

        $files = $base()->count();
        $downloads = (int) $base()->sum('downloads_count');
        $views = (int) $base()->sum('views_count');
        $bytes = (int) $base()->sum('size_bytes');

        // Uploads per day over the last 14 days.
        $rows = $base()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        $top = $base()->orderByDesc('downloads_count')->limit(5)->get();
        $byType = $base()->selectRaw('kind, COUNT(*) as c')->groupBy('kind')->pluck('c', 'kind');

        $rank = match (true) {
            $downloads >= 10000 => 'star',
            $downloads >= 1000 => 'pro',
            $downloads >= 100 => 'active',
            default => 'beginner',
        };

        return compact('files', 'downloads', 'views', 'bytes', 'days', 'top', 'byType', 'rank');
    }
}
