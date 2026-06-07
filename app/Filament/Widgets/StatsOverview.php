<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Developer;
use App\Models\DownloadLog;
use App\Models\Review;
use App\Models\Software;
use App\Models\UploadSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('dashboard.stat.content'), number_format(Software::count()))
                ->description(__('dashboard.stat.content_desc', ['count' => Software::where('status', ContentStatus::Published->value)->count()]))
                ->descriptionIcon('heroicon-m-cube')
                ->chart($this->dailySeries(Software::query()))
                ->color('primary'),

            Stat::make(__('dashboard.stat.downloads'), number_format((int) Software::sum('downloads_count')))
                ->description(__('dashboard.stat.downloads_desc', ['count' => DownloadLog::whereDate('created_at', today())->count()]))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->chart($this->dailySeries(DownloadLog::query()))
                ->color('success'),

            Stat::make(__('dashboard.stat.users'), number_format(User::count()))
                ->description(__('dashboard.stat.users_desc', ['count' => User::where('is_active', true)->count()]))
                ->descriptionIcon('heroicon-m-users')
                ->chart($this->dailySeries(User::query()))
                ->color('info'),

            Stat::make(__('dashboard.stat.storage'), $this->storageUsed())
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('gray'),

            Stat::make(__('dashboard.stat.reviews_pending'), number_format(Review::where('status', 'pending')->count()))
                ->description(__('dashboard.stat.reviews_desc'))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('warning'),

            Stat::make(__('dashboard.stat.messages'), number_format(Contact::where('is_read', false)->count()))
                ->description(__('dashboard.stat.messages_desc'))
                ->descriptionIcon('heroicon-m-envelope')
                ->color('warning'),

            Stat::make(__('dashboard.stat.uploads'), number_format(UploadSession::count()))
                ->description(__('dashboard.stat.uploads_desc', ['failed' => UploadSession::where('status', 'failed')->count()]))
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color('info'),

            Stat::make(
                __('dashboard.stat.developers').' · '.__('dashboard.stat.categories').' · '.__('dashboard.stat.articles'),
                Developer::count().' · '.Category::count().' · '.Article::count(),
            )
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('gray'),
        ];
    }

    /** Last-7-day daily counts for a sparkline. */
    private function dailySeries($query): array
    {
        $start = CarbonImmutable::today()->subDays(6);

        $rows = $query
            ->where('created_at', '>=', $start->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map->count();

        return collect(range(0, 6))
            ->map(fn ($i) => (int) ($rows[$start->addDays($i)->toDateString()] ?? 0))
            ->all();
    }

    private function storageUsed(): string
    {
        $bytes = (int) UploadSession::whereIn('status', ['uploaded', 'scanning', 'published'])->sum('size_bytes');
        if ($bytes <= 0) {
            return '0 B';
        }
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** min($i, 4)), 1).' '.$u[min($i, 4)];
    }
}
