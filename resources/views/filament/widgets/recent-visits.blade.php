<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-clock" :heading="__('analytics.recent')" compact>
        @php($rows = $this->rows())
        @php($deviceIcon = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button', 'bot' => 'fa-robot'])
        @if ($rows->isEmpty())
            <p class="text-sm text-gray-400 py-6 text-center">{{ __('analytics.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-xs text-gray-400">
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.time') }}</th>
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.location') }}</th>
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.ip') }}</th>
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.browser') }}</th>
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.os') }}</th>
                            <th class="py-2 pe-3 font-medium text-start">{{ __('analytics.col.page') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $v)
                            <tr class="text-gray-600 dark:text-gray-300">
                                <td class="whitespace-nowrap py-2 pe-3 text-xs text-gray-400" title="{{ $v->created_at }}">
                                    {{ $v->created_at?->diffForHumans() }}
                                </td>
                                <td class="whitespace-nowrap py-2 pe-3">
                                    <span class="text-base leading-none">{{ \App\Support\Geo::flag($v->country) }}</span>
                                    <span class="ms-1">{{ \App\Support\Geo::country($v->country) }}</span>
                                    @if ($v->city)
                                        <span class="text-xs text-gray-400">· {{ $v->city }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-2 pe-3 font-mono text-xs" dir="ltr">{{ $v->ip_address }}</td>
                                <td class="whitespace-nowrap py-2 pe-3">
                                    <i class="fa-solid {{ $deviceIcon[$v->device] ?? 'fa-circle-question' }} text-gray-400 me-1"></i>
                                    {{ $v->browser }}{{ $v->browser_version ? ' '.$v->browser_version : '' }}
                                </td>
                                <td class="whitespace-nowrap py-2 pe-3">{{ $v->os }}</td>
                                <td class="max-w-[220px] truncate py-2 pe-3" dir="ltr" title="{{ $v->path }}">{{ $v->path }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
