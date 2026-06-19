<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home. Curates which widgets show here (existing content stats +
 * a visitor-analytics summary). Full visitor detail lives on the dedicated
 * VisitorAnalytics page.
 */
class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            Widgets\AdminWelcome::class,
            Widgets\StatsOverview::class,
            Widgets\Analytics\VisitorsStats::class,
            Widgets\NewContentChart::class,
            Widgets\Analytics\TopCountries::class,
            Widgets\Analytics\TopPages::class,
            Widgets\ContentByTypeChart::class,
            Widgets\UploadsByStatusChart::class,
        ];
    }
}
