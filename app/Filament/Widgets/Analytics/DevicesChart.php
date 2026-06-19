<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\AnalyticsRange;
use Filament\Widgets\ChartWidget;

class DevicesChart extends ChartWidget
{
    use AnalyticsRange;

    protected static ?int $sort = 16;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '240px';

    public function getHeading(): string
    {
        return __('analytics.devices');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = (clone $this->baseQuery())
            ->selectRaw('device, count(*) as c')
            ->groupBy('device')
            ->pluck('c', 'device');

        $order = ['desktop', 'mobile', 'tablet'];
        $palette = ['desktop' => '#006C35', 'mobile' => '#3b82f6', 'tablet' => '#f59e0b'];

        $labels = [];
        $data = [];
        $colors = [];
        foreach ($order as $d) {
            $labels[] = __('analytics.device.'.$d);
            $data[] = (int) ($counts[$d] ?? 0);
            $colors[] = $palette[$d];
        }

        return [
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
