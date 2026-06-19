<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\AnalyticsRange;
use Filament\Widgets\Widget;

/**
 * Base for the "top N" breakdown cards (countries, cities, pages, browsers).
 * Subclasses provide a heading, icon and the ranked rows; the shared view
 * renders them as a labelled bar list with counts and percentages.
 */
abstract class BreakdownWidget extends Widget
{
    use AnalyticsRange;

    protected static string $view = 'filament.widgets.analytics-breakdown';

    protected int|string|array $columnSpan = 1;

    abstract public function heading(): string;

    abstract public function icon(): string;

    /** @return array<int,array{label:string,flag?:?string,sub?:?string,count:int,pct:int}> */
    abstract public function rows(): array;

    /**
     * Turn a "label => count" map into ranked rows with percentages.
     *
     * @param  iterable<string,int>  $counts
     * @param  callable(string):array{label:string,flag?:?string,sub?:?string}|null  $decorate
     * @return array<int,array{label:string,flag:?string,sub:?string,count:int,pct:int}>
     */
    protected function rank(iterable $counts, int $total, ?callable $decorate = null): array
    {
        $rows = [];

        foreach ($counts as $key => $count) {
            $meta = $decorate ? $decorate((string) $key) : ['label' => (string) $key];
            $rows[] = [
                'label' => $meta['label'] ?? (string) $key,
                'flag' => $meta['flag'] ?? null,
                'sub' => $meta['sub'] ?? null,
                'count' => (int) $count,
                'pct' => $total > 0 ? (int) round($count / $total * 100) : 0,
            ];
        }

        return $rows;
    }
}
