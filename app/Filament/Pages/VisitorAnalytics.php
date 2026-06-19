<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

/**
 * Full visitor analytics: visits over time, top countries (with flags), cities,
 * pages, browsers, device split and the most recent visits — all driven by a
 * shared date-range filter.
 */
class VisitorAnalytics extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('nav.group.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytics.nav');
    }

    public function getTitle(): string
    {
        return __('analytics.title');
    }

    public static function getRoutePath(): string
    {
        return '/visitor-analytics';
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Select::make('range')
                ->label(__('analytics.range'))
                ->options([
                    'today' => __('analytics.range_today'),
                    '7d' => __('analytics.range_7d'),
                    '30d' => __('analytics.range_30d'),
                    '90d' => __('analytics.range_90d'),
                    'all' => __('analytics.range_all'),
                ])
                ->default('30d')
                ->selectablePlaceholder(false)
                ->native(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            Analytics\VisitorsStats::class,
            Analytics\VisitsOverTime::class,
            Analytics\TopCountries::class,
            Analytics\TopCities::class,
            Analytics\TopPages::class,
            Analytics\TopBrowsers::class,
            Analytics\DevicesChart::class,
            Analytics\RecentVisits::class,
        ];
    }
}
