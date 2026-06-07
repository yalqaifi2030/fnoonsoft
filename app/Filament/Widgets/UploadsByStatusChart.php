<?php

namespace App\Filament\Widgets;

use App\Enums\UploadStatus;
use App\Models\UploadSession;
use Filament\Widgets\ChartWidget;

class UploadsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return __('dashboard.chart.uploads_status');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = UploadSession::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $palette = [
            'pending' => '#9ca3af',
            'uploaded' => '#3b82f6',
            'scanning' => '#f59e0b',
            'published' => '#16a34a',
            'failed' => '#dc2626',
        ];

        $labels = [];
        $data = [];
        $colors = [];
        foreach (UploadStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $colors[] = $palette[$status->value];
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
