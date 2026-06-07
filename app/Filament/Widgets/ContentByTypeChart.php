<?php

namespace App\Filament\Widgets;

use App\Enums\ContentType;
use App\Models\Software;
use Filament\Widgets\ChartWidget;

class ContentByTypeChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return __('dashboard.chart.by_type');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Software::query()
            ->selectRaw('content_type, COUNT(*) as aggregate')
            ->groupBy('content_type')
            ->pluck('aggregate', 'content_type');

        $palette = [
            'application' => '#006C35',
            'script' => '#3b82f6',
            'template' => '#f59e0b',
            'plugin' => '#a855f7',
        ];

        $labels = [];
        $data = [];
        $colors = [];
        foreach (ContentType::cases() as $type) {
            $labels[] = $type->label();
            $data[] = (int) ($counts[$type->value] ?? 0);
            $colors[] = $palette[$type->value];
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
