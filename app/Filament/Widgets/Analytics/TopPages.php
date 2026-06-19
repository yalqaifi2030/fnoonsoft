<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\PageTitle;

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

        // Stored paths are already normalised ("/software/slug"), so the title
        // map keys line up with the grouped paths.
        $titles = PageTitle::map($counts->keys()->all());

        return $this->rank($counts, $total, fn (string $path) => [
            'label' => $titles[$path] ?? $path,
        ]);
    }
}
