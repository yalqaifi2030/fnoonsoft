<?php

namespace App\Filament\Widgets\Analytics\Concerns;

use App\Models\Visit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Shared date-range handling for analytics widgets. Reads the host page's
 * `range` filter (today | 7d | 30d | 90d | all) and scopes the base visits
 * query to real humans within that window.
 */
trait AnalyticsRange
{
    use InteractsWithPageFilters;

    protected function range(): string
    {
        return $this->filters['range'] ?? '30d';
    }

    protected function rangeStart(): ?Carbon
    {
        return match ($this->range()) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'all' => null,
            default => now()->subDays(30),
        };
    }

    /** Human page views within the selected range (bots excluded). */
    protected function baseQuery(): Builder
    {
        $q = Visit::query()->where('is_bot', false);

        if ($start = $this->rangeStart()) {
            $q->where('created_at', '>=', $start);
        }

        return $q;
    }
}
