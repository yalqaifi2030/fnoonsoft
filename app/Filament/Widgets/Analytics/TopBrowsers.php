<?php

namespace App\Filament\Widgets\Analytics;

class TopBrowsers extends BreakdownWidget
{
    protected static ?int $sort = 14;

    public function heading(): string
    {
        return __('analytics.top_browsers');
    }

    public function icon(): string
    {
        return 'heroicon-o-window';
    }

    public function rows(): array
    {
        $counts = (clone $this->baseQuery())
            ->whereNotNull('browser')
            ->selectRaw('browser, count(*) as c')
            ->groupBy('browser')
            ->orderByDesc('c')
            ->limit(10)
            ->pluck('c', 'browser');

        $total = (clone $this->baseQuery())->whereNotNull('browser')->count();

        return $this->rank($counts, $total);
    }
}
