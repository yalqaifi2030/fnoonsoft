<?php

namespace App\Filament\Widgets\Analytics;

class TopPages extends BreakdownWidget
{
    protected static ?int $sort = 13;

    public function heading(): string
    {
        return __('analytics.top_pages');
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function rows(): array
    {
        $counts = (clone $this->baseQuery())
            ->whereNotNull('path')
            ->selectRaw('path, count(*) as c')
            ->groupBy('path')
            ->orderByDesc('c')
            ->limit(12)
            ->pluck('c', 'path');

        $total = (clone $this->baseQuery())->whereNotNull('path')->count();

        return $this->rank($counts, $total, fn (string $path) => [
            'label' => $path === '/' ? __('analytics.home') : $path,
        ]);
    }
}
