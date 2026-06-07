<?php

namespace App\Filament\Upload\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UploadStatsOverview extends BaseWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $base = Asset::query()->where('user_id', auth()->id());

        $bytes = (int) (clone $base)->sum('size_bytes');
        $downloads = (int) (clone $base)->sum('downloads_count');

        return [
            Stat::make(__('upload.stats.total'), number_format((clone $base)->count()))
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color('primary'),

            Stat::make(__('upload.stats.images'), number_format((clone $base)->where('kind', 'image')->count()))
                ->descriptionIcon('heroicon-m-photo')
                ->color('info'),

            Stat::make(__('upload.stats.downloads'), number_format($downloads))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make(__('upload.stats.storage'), $this->humanBytes($bytes))
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('gray'),
        ];
    }

    private function humanBytes(int $b): string
    {
        if ($b <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($b, 1024));

        return round($b / (1024 ** min($i, 4)), 1).' '.$u[min($i, 4)];
    }
}
