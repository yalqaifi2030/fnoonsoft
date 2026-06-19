<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\AnalyticsRange;
use Illuminate\Support\Collection;

class RecentVisits extends \Filament\Widgets\Widget
{
    use AnalyticsRange;

    protected static ?int $sort = 20;

    protected static string $view = 'filament.widgets.recent-visits';

    protected int|string|array $columnSpan = 'full';

    public function rows(): Collection
    {
        return (clone $this->baseQuery())
            ->latest('created_at')
            ->limit(30)
            ->get(['created_at', 'country', 'city', 'ip_address', 'browser', 'browser_version', 'os', 'device', 'path']);
    }
}
