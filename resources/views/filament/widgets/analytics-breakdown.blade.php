<x-filament-widgets::widget>
    <x-filament::section :icon="$this->icon()" :heading="$this->heading()" compact>
        @php($rows = $this->rows())
        @if (empty($rows))
            <p class="text-sm text-gray-400 py-6 text-center">{{ __('analytics.empty') }}</p>
        @else
            <ul class="space-y-2.5">
                @foreach ($rows as $row)
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2">
                                @if (! empty($row['flag']))
                                    <span class="text-base leading-none">{{ $row['flag'] }}</span>
                                @endif
                                <span class="truncate font-medium text-gray-700 dark:text-gray-200" dir="auto">{{ $row['label'] }}</span>
                                @if (! empty($row['sub']))
                                    <span class="truncate text-xs text-gray-400">· {{ $row['sub'] }}</span>
                                @endif
                            </span>
                            <span class="flex shrink-0 items-center gap-2 tabular-nums">
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format($row['count']) }}</span>
                                <span class="text-xs text-gray-400">{{ $row['pct'] }}%</span>
                            </span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full" style="background:rgba(120,120,120,.15)">
                            <div class="h-full rounded-full" style="width: {{ max(2, $row['pct']) }}%; background: var(--primary-600, #006C35)"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
