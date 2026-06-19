<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\AnalyticsRange;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorsStats extends BaseWidget
{
    use AnalyticsRange;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $base = $this->baseQuery();

        $visits = (clone $base)->count();
        $uniques = (clone $base)->distinct()->count('visitor_id');
        $today = Visit::where('is_bot', false)->whereDate('created_at', today())->count();
        $countries = (clone $base)->whereNotNull('country')->distinct()->count('country');

        return [
            Stat::make(__('analytics.stat.visits'), number_format($visits))
                ->description(__('analytics.stat.visits_desc'))
                ->descriptionIcon('heroicon-m-eye')
                ->chart($this->spark())
                ->color('primary'),

            Stat::make(__('analytics.stat.uniques'), number_format($uniques))
                ->description(__('analytics.stat.uniques_desc'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make(__('analytics.stat.today'), number_format($today))
                ->description(__('analytics.stat.today_desc'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make(__('analytics.stat.countries'), number_format($countries))
                ->description(__('analytics.stat.countries_desc'))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),
        ];
    }

    /** Last-14-day human visits sparkline. */
    private function spark(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Visit::where('is_bot', false)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map->count();

        return collect(range(0, 13))
            ->map(fn ($i) => (int) ($rows[$start->copy()->addDays($i)->toDateString()] ?? 0))
            ->all();
    }
}
