<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Models\Contact;
use App\Models\DownloadLog;
use App\Models\Review;
use App\Models\SecurityEvent;
use App\Models\Software;
use App\Models\UploadSession;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

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
        // Rolling 7-day window vs the previous 7 days, for week-over-week trend.
        $now = CarbonImmutable::now();
        $thisFrom = CarbonImmutable::today()->subDays(6)->startOfDay();
        $prevFrom = CarbonImmutable::today()->subDays(13)->startOfDay();
        $prevTo = CarbonImmutable::today()->subDays(7)->endOfDay();

        $wow = fn ($q) => $this->trend(
            (clone $q)->whereBetween('created_at', [$thisFrom, $now])->count(),
            (clone $q)->whereBetween('created_at', [$prevFrom, $prevTo])->count(),
        );

        $hasVisits = Schema::hasTable('visits');
        $hasSecurity = Schema::hasTable('security_events');

        return array_values(array_filter([
            // --- Primary KPIs with week-over-week growth -----------------
            Stat::make(__('dashboard.stat.content'), number_format(Software::count()))
                ->description($this->desc($wow(Software::query()), __('dashboard.stat.content_desc', ['count' => Software::where('status', ContentStatus::Published->value)->count()])))
                ->descriptionIcon($wow(Software::query())['icon'])
                ->chart($this->series(Software::query()))
                ->color('primary'),

            Stat::make(__('dashboard.stat.downloads'), $this->compact((int) Software::sum('downloads_count')))
                ->description($this->desc($wow(DownloadLog::query()), __('dashboard.stat.downloads_desc', ['count' => number_format(DownloadLog::whereDate('created_at', today())->count())])))
                ->descriptionIcon($wow(DownloadLog::query())['icon'])
                ->chart($this->series(DownloadLog::query()))
                ->color('success'),

            Stat::make(__('dashboard.stat.users'), number_format(User::count()))
                ->description($this->desc($wow(User::query()), __('dashboard.stat.users_desc', ['count' => User::where('is_active', true)->count()])))
                ->descriptionIcon($wow(User::query())['icon'])
                ->chart($this->series(User::query()))
                ->color('info'),

            $hasVisits
                ? Stat::make(__('dashboard.stat.visitors'), $this->compact(Visit::whereBetween('created_at', [$thisFrom, $now])->distinct('visitor_id')->count('visitor_id')))
                    ->description($this->desc($wow(Visit::query()), __('dashboard.stat.visitors_desc')))
                    ->descriptionIcon($wow(Visit::query())['icon'])
                    ->chart($this->series(Visit::query()))
                    ->color('warning')
                : null,

            // --- Operational / attention ---------------------------------
            Stat::make(__('dashboard.stat.storage'), $this->storageUsed())
                ->description(__('dashboard.stat.storage_desc'))
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('gray'),

            Stat::make(__('dashboard.stat.reviews_pending'), number_format(Review::where('status', 'pending')->count()))
                ->description(__('dashboard.stat.reviews_desc'))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color(Review::where('status', 'pending')->exists() ? 'warning' : 'gray'),

            Stat::make(__('dashboard.stat.messages'), number_format(Contact::where('is_read', false)->count()))
                ->description(__('dashboard.stat.messages_desc'))
                ->descriptionIcon('heroicon-m-envelope')
                ->color(Contact::where('is_read', false)->exists() ? 'warning' : 'gray'),

            $hasSecurity
                ? Stat::make(__('dashboard.stat.security'), number_format(SecurityEvent::whereDate('created_at', today())->count()))
                    ->description(__('dashboard.stat.security_desc'))
                    ->descriptionIcon('heroicon-m-shield-check')
                    ->color(SecurityEvent::whereDate('created_at', today())->exists() ? 'danger' : 'success')
                : Stat::make(__('dashboard.stat.uploads'), number_format(UploadSession::count()))
                    ->description(__('dashboard.stat.uploads_desc', ['failed' => UploadSession::where('status', 'failed')->count()]))
                    ->descriptionIcon('heroicon-m-cloud-arrow-up')
                    ->color('gray'),
        ]));
    }

    /** Week-over-week trend: percentage + up/down icon. */
    private function trend(int $current, int $previous): array
    {
        if ($previous <= 0) {
            $pct = $current > 0 ? 100 : 0;
        } else {
            $pct = (int) round((($current - $previous) / $previous) * 100);
        }
        $up = $pct >= 0;

        return [
            'pct' => $pct,
            'up' => $up,
            'icon' => $up ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
        ];
    }

    /** "↑ 8% this week · <sub-text>" */
    private function desc(array $trend, string $sub): string
    {
        $arrow = $trend['up'] ? '▲' : '▼';
        $pct = abs($trend['pct']);

        return $arrow.' '.$pct.'% '.__('dashboard.stat.this_week').' · '.$sub;
    }

    /** Last-7-day daily counts for a sparkline. */
    private function series($query): array
    {
        $start = CarbonImmutable::today()->subDays(6);

        $rows = (clone $query)
            ->where('created_at', '>=', $start->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map->count();

        return collect(range(0, 6))
            ->map(fn ($i) => (int) ($rows[$start->addDays($i)->toDateString()] ?? 0))
            ->all();
    }

    /** 12345 → "12.3K", 1200000 → "1.2M". */
    private function compact(int $n): string
    {
        if ($n < 1000) {
            return (string) $n;
        }
        if ($n < 1_000_000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.').'K';
        }

        return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.').'M';
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
