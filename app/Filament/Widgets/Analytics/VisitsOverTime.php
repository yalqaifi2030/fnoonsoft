<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\AnalyticsRange;
use App\Models\Visit;
use Filament\Widgets\ChartWidget;

class VisitsOverTime extends ChartWidget
{
    use AnalyticsRange;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '240px';

    public function getHeading(): string
    {
        return __('analytics.visits_over_time');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = ($this->rangeStart() ?? now()->subDays(89))->startOfDay();
        $days = min(max((int) $start->diffInDays(now()) + 1, 1), 120);

        $rows = Visit::where('is_bot', false)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map->count();

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('m/d');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => __('analytics.stat.visits'),
                'data' => $data,
                'borderColor' => '#006C35',
                'backgroundColor' => 'rgba(0,108,53,0.12)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
