<?php

namespace App\Filament\Widgets;

use App\Models\Software;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class NewContentChart extends ChartWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '240px';

    public function getHeading(): string
    {
        return __('dashboard.chart.new_content');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->subDays(13);

        $rows = Software::where('created_at', '>=', $start->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map->count();

        $labels = [];
        $data = [];
        foreach (range(0, 13) as $i) {
            $day = $start->addDays($i);
            $labels[] = $day->format('m/d');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => __('dashboard.chart.new_content'),
                'data' => $data,
                'backgroundColor' => 'rgba(0,108,53,.7)',
                'borderColor' => '#006C35',
                'borderRadius' => 6,
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
