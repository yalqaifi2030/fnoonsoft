<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Geo;

class TopCountries extends BreakdownWidget
{
    protected static ?int $sort = 11;

    public function heading(): string
    {
        return __('analytics.top_countries');
    }

    public function icon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public function rows(): array
    {
        $counts = (clone $this->baseQuery())
            ->whereNotNull('country')
            ->selectRaw('country, count(*) as c')
            ->groupBy('country')
            ->orderByDesc('c')
            ->limit(12)
            ->pluck('c', 'country');

        $total = (clone $this->baseQuery())->whereNotNull('country')->count();

        return $this->rank($counts, $total, fn (string $cc) => [
            'label' => Geo::country($cc),
            'flag' => Geo::flag($cc),
        ]);
    }
}
