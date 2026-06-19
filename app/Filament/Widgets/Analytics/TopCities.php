<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Geo;

class TopCities extends BreakdownWidget
{
    protected static ?int $sort = 12;

    public function heading(): string
    {
        return __('analytics.top_cities');
    }

    public function icon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public function rows(): array
    {
        $records = (clone $this->baseQuery())
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, max(country) as country, count(*) as c')
            ->groupBy('city')
            ->orderByDesc('c')
            ->limit(12)
            ->get();

        $total = (clone $this->baseQuery())->whereNotNull('city')->where('city', '!=', '')->count();

        $rows = [];
        foreach ($records as $r) {
            $rows[] = [
                'label' => $r->city,
                'flag' => Geo::flag($r->country),
                'sub' => Geo::country($r->country),
                'count' => (int) $r->c,
                'pct' => $total > 0 ? (int) round($r->c / $total * 100) : 0,
            ];
        }

        return $rows;
    }
}
