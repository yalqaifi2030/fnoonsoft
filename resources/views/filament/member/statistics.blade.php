<x-filament-panels::page>
@php
    $fmt = function ($b) {
        $b = (int) $b; if ($b <= 0) return '0 B';
        $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
    $maxUp = max(1, collect($days)->max('count'));
    $maxType = max(1, (int) ($byType->max() ?? 1));
    $ranks = [
        'beginner' => ['label' => __('member.stats.rank_beginner'), 'color' => '#6b7280', 'icon' => 'fa-seedling'],
        'active'   => ['label' => __('member.stats.rank_active'),   'color' => '#3b82f6', 'icon' => 'fa-bolt'],
        'pro'      => ['label' => __('member.stats.rank_pro'),      'color' => '#006C35', 'icon' => 'fa-star'],
        'star'     => ['label' => __('member.stats.rank_star'),     'color' => '#C9A961', 'icon' => 'fa-crown'],
    ];
    $r = $ranks[$rank] ?? $ranks['beginner'];
    $cards = [
        ['label' => __('member.stats.files'),     'value' => number_format($files),     'icon' => 'fa-folder',     'color' => '#006C35'],
        ['label' => __('member.stats.downloads'), 'value' => number_format($downloads), 'icon' => 'fa-arrow-down', 'color' => '#3b82f6'],
        ['label' => __('member.stats.views'),     'value' => number_format($views),     'icon' => 'fa-eye',        'color' => '#8b5cf6'],
        ['label' => __('member.stats.storage'),   'value' => $fmt($bytes),              'icon' => 'fa-hard-drive', 'color' => '#b8860b'],
    ];
    $typeMeta = [
        'image' => ['label' => __('asset_admin.kind_image'), 'icon' => 'fa-image',       'color' => '#3b82f6'],
        'pdf'   => ['label' => __('asset_admin.kind_pdf'),   'icon' => 'fa-file-pdf',    'color' => '#ef4444'],
        'file'  => ['label' => __('asset_admin.kind_file'),  'icon' => 'fa-box-archive', 'color' => '#f59e0b'],
    ];
@endphp

<style>
    .st-grid4 { display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:1rem; }
    @media(min-width:1024px){ .st-grid4 { grid-template-columns: repeat(4,minmax(0,1fr)); } }
    .st-grid2 { display:grid; grid-template-columns: minmax(0,1fr); gap:1.5rem; }
    @media(min-width:1024px){ .st-grid2 { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    .st-card { border:1px solid #eef0f2; border-radius:1rem; background:#fff; padding:1.1rem; }
    .dark .st-card { background:#0b1220; border-color:rgba(255,255,255,.08); }
</style>

<div class="space-y-6">

    {{-- Rank banner --}}
    <div class="rounded-2xl p-5 text-white" style="background: linear-gradient(120deg, {{ $r['color'] }}, #00472a);">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-2xl">
                <i class="fa-solid {{ $r['icon'] }}"></i>
            </span>
            <div>
                <div class="text-xs opacity-80">{{ __('member.stats.your_rank') }}</div>
                <div class="text-2xl font-extrabold">{{ $r['label'] }}</div>
            </div>
        </div>
    </div>

    {{-- Headline counters --}}
    <div class="st-grid4">
        @foreach ($cards as $c)
            <div class="st-card flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl text-lg" style="background: {{ $c['color'] }}1a; color: {{ $c['color'] }};">
                    <i class="fa-solid {{ $c['icon'] }}"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-xl font-extrabold text-gray-900 dark:text-white" dir="ltr">{{ $c['value'] }}</div>
                    <div class="truncate text-xs text-gray-500">{{ $c['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="st-grid2">
        {{-- Uploads, last 14 days --}}
        <div class="st-card">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('member.stats.uploads_14') }}</h3>
            <div class="flex items-end gap-1" style="height: 9rem;">
                @foreach ($days as $d)
                    <div class="flex flex-1 flex-col items-center justify-end" title="{{ $d['date'] }}: {{ $d['count'] }}">
                        <div class="w-full rounded-t" style="height: {{ max(3, round($d['count'] / $maxUp * 100)) }}%; min-height:3px; background:#006C35;"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-gray-400" dir="ltr">
                <span>{{ \Illuminate\Support\Carbon::parse($days[0]['date'])->format('d/m') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse($days[count($days) - 1]['date'])->format('d/m') }}</span>
            </div>
        </div>

        {{-- By type --}}
        <div class="st-card">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('member.stats.by_type') }}</h3>
            @forelse ($byType as $kind => $count)
                @php $m = $typeMeta[$kind] ?? ['label' => $kind, 'icon' => 'fa-file', 'color' => '#6b7280']; @endphp
                <div class="mb-3">
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><i class="fa-solid {{ $m['icon'] }}" style="color: {{ $m['color'] }};"></i> {{ $m['label'] }}</span>
                        <span class="font-bold text-gray-900 dark:text-white" dir="ltr">{{ number_format($count) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full" style="width: {{ round($count / $maxType * 100) }}%; background: {{ $m['color'] }};"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">{{ __('member.files.empty') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Top files --}}
    <div class="st-card overflow-hidden p-0">
        <h3 class="border-b border-gray-100 px-5 py-4 text-sm font-semibold text-gray-900 dark:border-white/10 dark:text-white">{{ __('member.stats.top_files') }}</h3>
        @forelse ($top as $i => $a)
            <div class="flex items-center gap-3 border-b border-gray-50 px-5 py-3 last:border-0 dark:border-white/5">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-500 dark:bg-white/10">{{ $i + 1 }}</span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $a->original_name }}</div>
                </div>
                <span class="flex items-center gap-1 text-xs font-bold text-primary-600" dir="ltr"><i class="fa-solid fa-arrow-down"></i> {{ number_format($a->downloads_count) }}</span>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('member.files.empty') }}</p>
        @endforelse
    </div>

</div>
</x-filament-panels::page>
