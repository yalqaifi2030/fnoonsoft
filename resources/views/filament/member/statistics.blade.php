<x-filament-panels::page>
@php
    $fmt = function ($b) {
        $b = (int) $b; if ($b <= 0) return '0 B';
        $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
    $totalUp = (int) collect($days)->sum('count');
    $maxUp = max(1, (int) collect($days)->max('count'));

    $ranks = [
        'beginner' => ['label' => __('member.stats.rank_beginner'), 'color' => '#6b7280', 'icon' => 'fa-seedling'],
        'active'   => ['label' => __('member.stats.rank_active'),   'color' => '#3b82f6', 'icon' => 'fa-bolt'],
        'pro'      => ['label' => __('member.stats.rank_pro'),      'color' => '#006C35', 'icon' => 'fa-star'],
        'star'     => ['label' => __('member.stats.rank_star'),     'color' => '#C9A961', 'icon' => 'fa-crown'],
    ];
    $r = $ranks[$rank] ?? $ranks['beginner'];
    $nextKey = ['beginner' => 'active', 'active' => 'pro', 'pro' => 'star', 'star' => null][$rank] ?? null;
    $nextAt  = ['beginner' => 100, 'active' => 1000, 'pro' => 10000, 'star' => null][$rank] ?? null;
    $rankPct = $nextAt ? min(100, round($downloads / $nextAt * 100)) : 100;

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
    $totalType = max(0, (int) $byType->sum());
    $segments = []; $acc = 0;
    foreach ($byType as $kind => $count) {
        $m = $typeMeta[$kind] ?? ['label' => $kind, 'icon' => 'fa-file', 'color' => '#6b7280'];
        $pct = $totalType ? $count / $totalType * 100 : 0;
        $segments[] = ['color' => $m['color'], 'from' => $acc, 'to' => $acc + $pct, 'label' => $m['label'], 'count' => (int) $count, 'pct' => $pct, 'icon' => $m['icon']];
        $acc += $pct;
    }
    $conic = $totalType
        ? collect($segments)->map(fn ($s) => $s['color'].' '.round($s['from'], 2).'% '.round($s['to'], 2).'%')->implode(', ')
        : '#e5e7eb 0% 100%';
    $medals = ['#C9A961', '#9ca3af', '#cd7f32'];
@endphp

<style>
    .st-grid4 { display:grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap:1rem; }
    @media(min-width:1024px){ .st-grid4 { grid-template-columns: repeat(4,minmax(0,1fr)); } }
    .st-grid2 { display:grid; grid-template-columns: minmax(0,1fr); gap:1.5rem; }
    @media(min-width:1024px){ .st-grid2 { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    .st-card { border:1px solid #eef0f2; border-radius:1rem; background:#fff; padding:1.25rem; }
    .dark .st-card { background:#0b1220; border-color:rgba(255,255,255,.08); }
    .st-stat { position:relative; overflow:hidden; transition:transform .15s ease, box-shadow .15s ease; }
    .st-stat:hover { transform:translateY(-2px); box-shadow:0 12px 24px -14px rgba(0,0,0,.25); }
    .st-stat::before { content:''; position:absolute; inset-inline:0; top:0; height:3px; background:var(--accent); }
    .st-bar { transition:height .6s cubic-bezier(.2,.7,.3,1); }
</style>

<div class="space-y-6">

    {{-- Rank banner with progress to the next rank --}}
    <div style="border-radius:1.25rem; padding:1.5rem; color:#fff; background:linear-gradient(120deg, {{ $r['color'] }}, #00472a); box-shadow:0 18px 40px -22px {{ $r['color'] }};">
        <div style="display:flex; align-items:center; gap:1rem;">
            <span style="display:flex; height:3.5rem; width:3.5rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:1rem; background:rgba(255,255,255,.16); font-size:1.5rem;">
                <i class="fa-solid {{ $r['icon'] }}"></i>
            </span>
            <div style="flex:1; min-width:0;">
                <div style="font-size:.72rem; opacity:.85;">{{ __('member.stats.your_rank') }}</div>
                <div style="font-size:1.6rem; font-weight:800; line-height:1.1;">{{ $r['label'] }}</div>
            </div>
            <div style="text-align:center; flex:0 0 auto;">
                <div style="font-size:1.4rem; font-weight:800;" dir="ltr">{{ number_format($downloads) }}</div>
                <div style="font-size:.65rem; opacity:.85;">{{ __('member.stats.downloads') }}</div>
            </div>
        </div>

        @if ($nextKey)
            <div style="margin-top:1.1rem;">
                <div style="display:flex; justify-content:space-between; font-size:.68rem; opacity:.9; margin-bottom:.35rem;">
                    <span>{{ __('member.stats.next') }}: {{ $ranks[$nextKey]['label'] }}</span>
                    <span dir="ltr">{{ number_format($downloads) }} / {{ number_format($nextAt) }}</span>
                </div>
                <div style="height:.5rem; width:100%; border-radius:9999px; background:rgba(255,255,255,.2); overflow:hidden;">
                    <div class="st-bar" style="height:100%; border-radius:9999px; width:{{ max(2, $rankPct) }}%; background:rgba(255,255,255,.9);"></div>
                </div>
            </div>
        @else
            <div style="margin-top:.9rem; font-size:.72rem; opacity:.9;"><i class="fa-solid fa-trophy"></i> {{ __('member.stats.max_level') }}</div>
        @endif
    </div>

    {{-- Headline counters --}}
    <div class="st-grid4">
        @foreach ($cards as $c)
            <div class="st-card st-stat" style="--accent: {{ $c['color'] }};">
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <span style="display:flex; height:2.75rem; width:2.75rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:.85rem; font-size:1.1rem; background:{{ $c['color'] }}1a; color:{{ $c['color'] }};">
                        <i class="fa-solid {{ $c['icon'] }}"></i>
                    </span>
                    <div style="min-width:0;">
                        <div class="text-gray-900 dark:text-white" style="font-size:1.35rem; font-weight:800; line-height:1;" dir="ltr">{{ $c['value'] }}</div>
                        <div class="text-gray-500" style="font-size:.72rem; margin-top:.15rem;">{{ $c['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="st-grid2">
        {{-- Uploads, last 14 days --}}
        <div class="st-card">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem;">
                <h3 class="text-gray-900 dark:text-white" style="font-size:.85rem; font-weight:700;">{{ __('member.stats.uploads_14') }}</h3>
                <div class="text-gray-500" style="display:flex; gap:.9rem; font-size:.68rem;">
                    <span>{{ __('member.stats.total') }}: <b class="text-gray-900 dark:text-white" dir="ltr">{{ $totalUp }}</b></span>
                    <span>{{ __('member.stats.peak') }}: <b class="text-gray-900 dark:text-white" dir="ltr">{{ $maxUp }}</b></span>
                </div>
            </div>
            <div style="display:flex; align-items:flex-end; gap:3px; height:9rem;">
                @foreach ($days as $d)
                    <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;" title="{{ $d['date'] }}: {{ $d['count'] }}">
                        @if ($d['count'] > 0)
                            <span style="font-size:.58rem; font-weight:700; color:#006C35; margin-bottom:2px;" dir="ltr">{{ $d['count'] }}</span>
                        @endif
                        <div class="st-bar" style="width:100%; border-radius:5px 5px 0 0; height:{{ $d['count'] > 0 ? max(8, round($d['count'] / $maxUp * 100)) : 2 }}%; background:{{ $d['count'] > 0 ? 'linear-gradient(180deg,#00b057,#006C35)' : 'rgba(128,128,128,.16)' }};"></div>
                    </div>
                @endforeach
            </div>
            <div class="text-gray-400" style="margin-top:.6rem; display:flex; justify-content:space-between; font-size:.62rem;" dir="ltr">
                <span>{{ \Illuminate\Support\Carbon::parse($days[0]['date'])->format('d/m') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse($days[count($days) - 1]['date'])->format('d/m') }}</span>
            </div>
        </div>

        {{-- By type — donut --}}
        <div class="st-card">
            <h3 class="text-gray-900 dark:text-white" style="font-size:.85rem; font-weight:700; margin-bottom:1rem;">{{ __('member.stats.by_type') }}</h3>
            @if ($totalType > 0)
                <div style="display:flex; align-items:center; gap:1.5rem;">
                    <div style="position:relative; width:8rem; height:8rem; flex:0 0 auto;">
                        <div style="width:100%; height:100%; border-radius:50%; background:conic-gradient({{ $conic }});"></div>
                        <div class="st-card" style="position:absolute; inset:24%; border-radius:50%; padding:0; display:flex; flex-direction:column; align-items:center; justify-content:center; border:0;">
                            <div class="text-gray-900 dark:text-white" style="font-size:1.5rem; font-weight:800; line-height:1;" dir="ltr">{{ $totalType }}</div>
                            <div class="text-gray-400" style="font-size:.6rem;">{{ __('asset_admin.kind_file') }}</div>
                        </div>
                    </div>
                    <div style="flex:1; min-width:0;">
                        @foreach ($segments as $s)
                            <div style="display:flex; align-items:center; gap:.55rem; margin-bottom:.7rem;">
                                <span style="width:.7rem; height:.7rem; flex:0 0 auto; border-radius:50%; background:{{ $s['color'] }};"></span>
                                <i class="fa-solid {{ $s['icon'] }}" style="color:{{ $s['color'] }}; font-size:.8rem;"></i>
                                <span class="text-gray-600 dark:text-gray-300" style="flex:1; font-size:.8rem;">{{ $s['label'] }}</span>
                                <span class="text-gray-900 dark:text-white" style="font-size:.8rem; font-weight:700;" dir="ltr">{{ $s['count'] }} · {{ round($s['pct']) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-gray-400" style="padding:2rem 0; text-align:center; font-size:.85rem;">{{ __('member.files.empty') }}</p>
            @endif
        </div>
    </div>

    {{-- Top files --}}
    <div class="st-card" style="padding:0; overflow:hidden;">
        <h3 class="text-gray-900 dark:text-white" style="border-bottom:1px solid rgba(128,128,128,.14); padding:1rem 1.25rem; font-size:.85rem; font-weight:700;">{{ __('member.stats.top_files') }}</h3>
        @forelse ($top as $i => $a)
            <div style="display:flex; align-items:center; gap:.75rem; padding:.8rem 1.25rem; {{ ! $loop->last ? 'border-bottom:1px solid rgba(128,128,128,.08);' : '' }}">
                <span style="display:flex; height:1.9rem; width:1.9rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:.6rem; font-size:.72rem; font-weight:800; {{ $i < 3 ? 'color:#fff; background:'.$medals[$i].';' : 'color:#6b7280;' }}" class="{{ $i < 3 ? '' : 'bg-gray-100 dark:bg-white/10' }}" dir="ltr">{{ $i + 1 }}</span>
                <div style="flex:1; min-width:0;">
                    <div class="text-gray-900 dark:text-white" style="font-size:.85rem; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $a->original_name }}</div>
                    <div class="text-gray-400" style="font-size:.68rem;" dir="ltr">{{ $fmt($a->size_bytes) }}</div>
                </div>
                <span style="display:flex; align-items:center; gap:.3rem; font-size:.8rem; font-weight:700; color:#006C35;" dir="ltr"><i class="fa-solid fa-arrow-down"></i> {{ number_format($a->downloads_count) }}</span>
            </div>
        @empty
            <p class="text-gray-400" style="padding:2rem; text-align:center; font-size:.85rem;">{{ __('member.files.empty') }}</p>
        @endforelse
    </div>

</div>
</x-filament-panels::page>
