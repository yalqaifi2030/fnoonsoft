@php($d = $record->data ?? [])
<div class="space-y-4 text-sm" dir="ltr">
    <h3 class="font-bold text-base text-gray-900 dark:text-white" dir="auto">{{ $record->title }}</h3>

    @switch($key)
        @case('playground')
            @foreach (['html' => 'HTML', 'css' => 'CSS', 'js' => 'JavaScript'] as $f => $label)
                @if (! empty($d[$f]))
                    <div>
                        <div class="text-xs font-semibold text-gray-500 mb-1">{{ $label }}</div>
                        <pre class="rounded-lg bg-gray-900 text-green-200 p-3 overflow-x-auto text-xs leading-relaxed">{{ $d[$f] }}</pre>
                    </div>
                @endif
            @endforeach
            @break

        @case('arduino')
            <div class="flex gap-2">
                <span class="rounded-md bg-sky-100 text-sky-700 px-2 py-0.5 text-xs font-semibold">{{ $d['type'] ?? 'blink' }}</span>
                <span class="rounded-md bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-semibold">{{ $d['delay'] ?? 0 }} ms</span>
            </div>
            <pre class="rounded-lg bg-gray-900 text-sky-200 p-3 overflow-x-auto text-xs leading-relaxed">{{ $d['code'] ?? '' }}</pre>
            @break

        @case('snippets')
            <div class="flex gap-2">
                <span class="rounded-md bg-amber-100 text-amber-700 px-2 py-0.5 text-xs font-semibold">{{ $d['category'] ?? '' }}</span>
                <span class="rounded-md bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-semibold">{{ $d['lang'] ?? '' }}</span>
            </div>
            <pre class="rounded-lg bg-gray-900 text-amber-200 p-3 overflow-x-auto text-xs leading-relaxed">{{ $d['code'] ?? '' }}</pre>
            @break

        @case('ai')
            <div><span class="rounded-md bg-fuchsia-100 text-fuchsia-700 px-2 py-0.5 text-xs font-semibold">degree {{ $d['degree'] ?? 1 }}</span></div>
            <div>
                <div class="text-xs font-semibold text-gray-500 mb-1">Data points (x,y)</div>
                <pre class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 overflow-x-auto text-xs">{{ $d['points'] ?? '' }}</pre>
            </div>
            @break

        @case('security')
            <div class="flex gap-2">
                <span class="rounded-md bg-rose-100 text-rose-700 px-2 py-0.5 text-xs font-semibold">{{ $d['type'] ?? 'caesar' }}</span>
                @if (! empty($d['icon']))
                    <span class="rounded-md bg-gray-100 text-gray-600 px-2 py-0.5 text-xs"><i class="{{ $d['icon'] }}"></i> {{ $d['icon'] }}</span>
                @endif
            </div>
            @if (($d['type'] ?? '') === 'custom')
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-1">Custom HTML</div>
                    <pre class="rounded-lg bg-gray-900 text-emerald-200 p-3 overflow-x-auto text-xs leading-relaxed">{{ $d['html'] ?? '' }}</pre>
                    <div class="text-xs font-semibold text-gray-500 mt-3 mb-1">Live preview</div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3" dir="auto">{!! $d['html'] ?? '' !!}</div>
                </div>
            @else
                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-1">Sample input</div>
                    <pre class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-xs">{{ $d['sample'] ?? '—' }}</pre>
                </div>
            @endif
            @break
    @endswitch
</div>
