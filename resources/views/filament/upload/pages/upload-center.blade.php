<x-filament-panels::page>
    @php
        $fmt = function (int $b) {
            if ($b <= 0) return '0 B';
            $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
            return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
        };
        // The member panel sets a quota in the stats; the staff upload panel does not.
        $isMember = ! empty($stats['quota']);
        $me = auth()->user();
    @endphp

    <div class="space-y-6">

        {{-- ===== HERO HEADER ===== --}}
        @php $isLocal = ($storage ?? 'local') === 'local'; @endphp
        <div class="fc-hero">
            <span class="fc-hero-blob fc-hero-blob--a"></span>
            <span class="fc-hero-blob fc-hero-blob--b"></span>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="fc-hero-icon">
                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-8 w-8" />
                </span>
                <div class="min-w-0 flex-1">
                    @if ($isMember)
                        <h1 class="inline-flex items-center gap-2 text-2xl font-extrabold leading-tight text-white">
                            {{ __('member.welcome', ['name' => $me?->name ?: __('member.brand')]) }} 👋
                            @if ($me && $me->memberTier()->hasBadge())
                                <i class="{{ $me->memberTier()->icon() }} text-lg" style="color: {{ $me->memberTier()->color() }};" title="{{ $me->memberTier()->label() }}"></i>
                            @endif
                        </h1>
                        <p class="mt-1 text-sm text-green-100/90">{{ __('member.hero_note') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="/dashboard/assets" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                                <i class="fa-solid fa-folder"></i> {{ __('member.files.nav') }}
                            </a>
                            <a href="/dashboard/statistics" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                                <i class="fa-solid fa-chart-simple"></i> {{ __('member.stats.nav') }}
                            </a>
                            @if ($me?->publicProfileUrl())
                                <a href="{{ $me->publicProfileUrl() }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/25">
                                    <i class="fa-solid fa-user"></i> {{ __('member.public_page') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <h1 class="text-2xl font-extrabold leading-tight text-white">{{ __('upload.center.title') }}</h1>
                        <p class="mt-1 text-sm text-green-100/90">{{ __('upload.center.dashboard_note') }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="fc-hero-pill {{ $isLocal ? 'is-amber' : 'is-green' }}" title="{{ $isLocal ? __('upload.storage.local_note') : __('upload.storage.cloud_note') }}">
                        @if ($isLocal)
                            <i class="fa-solid fa-server"></i> {{ __('upload.storage.local_label') }}
                        @else
                            <i class="fa-solid fa-circle text-[7px] text-green-300"></i>
                            {{ __('upload.storage.active', ['provider' => $storageLabel]) }}
                            @if (! empty($storageBucket))
                                <span class="opacity-70">·</span><span class="font-mono" dir="ltr">{{ $storageBucket }}</span>
                            @endif
                        @endif
                    </span>
                    <span class="fc-hero-pill is-white">
                        <i class="fa-solid fa-bolt"></i> {{ number_format($maxBytes / 1073741824, 0) }} GB
                    </span>
                </div>
            </div>
        </div>

        {{-- ===== MEMBER STORAGE (member panel only) ===== --}}
        @if (! empty($stats['quota']))
            @php
                $qUsed = max(0, $stats['quota'] - $stats['remaining']);
                $qPct = $stats['quota'] > 0 ? min(100, round($qUsed / $stats['quota'] * 100)) : 0;
                $qColor = $qPct >= 90 ? '#ef4444' : ($qPct >= 70 ? '#f59e0b' : '#006C35');
                $qColor2 = $qPct >= 90 ? '#f87171' : ($qPct >= 70 ? '#fbbf24' : '#00a050');
            @endphp
            <div class="border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900" style="border-radius:1rem; overflow:hidden;">
                {{-- Header: icon + title + big percentage --}}
                <div style="display:flex; align-items:center; gap:1rem; padding:1.25rem 1.25rem 1rem;">
                    <span style="display:flex; height:3rem; width:3rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:1rem; color:#fff; background:linear-gradient(135deg,#006C35,#00a050); box-shadow:0 6px 14px -4px rgba(0,108,53,.5);">
                        <i class="fa-solid fa-database" style="font-size:1.1rem;"></i>
                    </span>
                    <div style="min-width:0; flex:1 1 auto;">
                        <div class="text-gray-900 dark:text-white" style="font-weight:700; font-size:.9rem;">{{ __('member.quota.title') }}</div>
                        <div class="text-gray-400" style="font-size:.75rem; margin-top:.15rem;" dir="ltr">{{ $fmt($qUsed) }} {{ __('member.quota.of') }} {{ $fmt($stats['quota']) }}</div>
                    </div>
                    <div style="font-size:1.65rem; font-weight:800; line-height:1; color:{{ $qColor }};" dir="ltr">{{ $qPct }}%</div>
                </div>

                {{-- Gradient progress bar --}}
                <div style="padding:0 1.25rem;">
                    <div class="bg-gray-100 dark:bg-white/10" style="height:.7rem; width:100%; border-radius:9999px; overflow:hidden;">
                        <div style="height:100%; border-radius:9999px; width:{{ max(2, $qPct) }}%; background:linear-gradient(90deg,{{ $qColor }},{{ $qColor2 }}); transition:width .7s ease;"></div>
                    </div>
                </div>

                @if ($qPct >= 100)
                    <p class="text-red-600" style="padding:.75rem 1.25rem 0; font-size:.75rem; font-weight:600;">{{ __('member.quota.full') }}</p>
                @endif

                {{-- Footer: used | remaining (inline flex → always two columns) --}}
                <div style="display:flex; margin-top:1rem; border-top:1px solid rgba(128,128,128,.18);">
                    <div style="flex:1; text-align:center; padding:.85rem 1rem; border-inline-end:1px solid rgba(128,128,128,.18);">
                        <div class="text-gray-900 dark:text-white" style="font-size:1.05rem; font-weight:800;" dir="ltr">{{ $fmt($qUsed) }}</div>
                        <div class="text-gray-400" style="font-size:.72rem; margin-top:.1rem;">{{ __('member.quota.used') }}</div>
                    </div>
                    <div style="flex:1; text-align:center; padding:.85rem 1rem;">
                        <div class="text-gray-900 dark:text-white" style="font-size:1.05rem; font-weight:800;" dir="ltr">{{ $fmt($stats['remaining']) }}</div>
                        <div class="text-gray-400" style="font-size:.72rem; margin-top:.1rem;">{{ __('member.quota.remaining') }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== STATS ===== --}}
        <div class="fc-grid4">
            @php
                $cards = [
                    ['label' => __('upload.stats.total'),     'value' => number_format($stats['total']),     'icon' => 'heroicon-o-cloud-arrow-up',  'accent' => '#006C35'],
                    ['label' => __('upload.stats.images'),    'value' => number_format($stats['images']),    'icon' => 'heroicon-o-photo',           'accent' => '#3b82f6'],
                    ['label' => __('upload.stats.downloads'), 'value' => number_format($stats['downloads']), 'icon' => 'heroicon-o-arrow-down-tray', 'accent' => '#8b5cf6'],
                    ['label' => __('upload.stats.storage'),   'value' => $fmt($stats['bytes']),              'icon' => 'heroicon-o-circle-stack',    'accent' => '#b8860b'],
                ];
            @endphp
            @foreach ($cards as $c)
                <div class="fc-stat" style="--accent: {{ $c['accent'] }}; --accent-soft: {{ $c['accent'] }}1f;">
                    <span class="fc-stat-icon">
                        <x-filament::icon :icon="$c['icon']" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <div class="text-2xl font-extrabold leading-tight text-gray-900 dark:text-white" dir="ltr">{{ $c['value'] }}</div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $c['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== TWO UPLOAD ZONES ===== --}}
        <div class="fc-grid2">
            {{-- Zone A: files & archives (multipart) --}}
            <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <div class="px-6 py-4 text-white relative overflow-hidden" style="background: linear-gradient(120deg, #006C35, #00582b);">
                    <div class="relative flex items-center gap-3">
                        <span class="h-10 w-10 rounded-xl bg-white/15 inline-flex items-center justify-center shrink-0">
                            <x-filament::icon icon="heroicon-o-archive-box-arrow-down" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold">{{ __('upload.zones.files_title') }}</h2>
                            <p class="text-xs text-green-100 truncate">{{ __('upload.zones.files_sub', ['max' => number_format($maxBytes / 1073741824, 0)]) }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div wire:ignore x-data="fnoonFiles()"><div id="fnoon-files"></div></div>
                </div>
            </div>

            {{-- Zone B: images & PDF (direct) --}}
            <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <div class="px-6 py-4 text-white relative overflow-hidden" style="background: linear-gradient(120deg, #b88a2e, #8a6420);">
                    <div class="relative flex items-center gap-3">
                        <span class="h-10 w-10 rounded-xl bg-white/15 inline-flex items-center justify-center shrink-0">
                            <x-filament::icon icon="heroicon-o-photo" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-bold">{{ __('upload.zones.media_title') }}</h2>
                            <p class="text-xs text-amber-100 truncate">{{ __('upload.zones.media_sub') }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div wire:ignore x-data="fnoonMedia()"><div id="fnoon-media"></div></div>
                </div>
            </div>
        </div>

        {{-- ===== SHARE-KIT RESULTS ===== --}}
        <div wire:ignore x-data="shareResults()" x-show="items.length" x-cloak
             class="rounded-2xl border border-saudi-green/20 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/10 px-6 py-4">
                <div>
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-share" class="h-5 w-5 text-primary-600" />
                        {{ __('upload.zones.results_title') }}
                        <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary-600" x-text="items.length"></span>
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-400">{{ __('upload.zones.results_hint') }}</p>
                </div>
                <button @click="clear()" class="text-xs font-medium text-gray-400 hover:text-red-500">{{ __('upload.zones.clear') }}</button>
            </div>

            <div class="sk-list">
                <template x-for="a in items" :key="a.slug">
                    <div class="sk-card">
                        {{-- head --}}
                        <div class="sk-card-head">
                            <template x-if="a.preview">
                                <a :href="a.direct" target="_blank" class="sk-thumb"><img :src="a.preview" alt=""></a>
                            </template>
                            <template x-if="!a.preview">
                                <span class="sk-thumb sk-thumb--file"><i class="fa-solid fa-file-arrow-down"></i></span>
                            </template>

                            <div class="min-w-0 flex-1">
                                <div class="sk-name" x-text="a.name"></div>
                                <div class="sk-meta">
                                    <span class="sk-chip" x-text="a.kind"></span>
                                    <span x-show="a.size" x-text="human(a.size)"></span>
                                    <span x-show="a.pages" x-text="a.pages + ' p'"></span>
                                </div>
                            </div>

                            <div style="margin-inline-start:auto; display:flex; gap:.5rem; flex:0 0 auto;">
                                <button type="button" @click="copy(a.download, a.slug + '-dl')" class="sk-page-btn" style="margin:0; background:#374151;">
                                    <i class="fa-solid" :class="copiedKey === (a.slug + '-dl') ? 'fa-check' : 'fa-link'"></i>
                                    <span x-text="copiedKey === (a.slug + '-dl') ? '{{ __('asset_admin.action.copied') }}' : '{{ __('asset_admin.action.copy_link') }}'"></span>
                                </button>
                                <a :href="a.page" target="_blank" class="sk-page-btn" style="margin:0;">
                                    <i class="fa-solid fa-up-right-from-square"></i>
                                    <span>{{ __('upload.zones.open_page') }}</span>
                                </a>
                            </div>
                        </div>

                        {{-- codes --}}
                        <div class="sk-codes">
                            <template x-for="(b, i) in a.kit" :key="i">
                                <div class="sk-field" :class="copiedKey === (a.slug + i) ? 'is-copied' : ''">
                                    <div class="sk-field-top">
                                        <span class="sk-field-label">
                                            <i :class="icon(b.key)"></i>
                                            <span x-text="b.label"></span>
                                        </span>
                                        <button type="button" @click="copy(b.code, a.slug + i)" class="sk-copy">
                                            <i class="fa-solid" :class="copiedKey === (a.slug + i) ? 'fa-check' : 'fa-copy'"></i>
                                            <span x-text="copiedKey === (a.slug + i) ? '{{ __('upload.zones.copied') }}' : '{{ __('upload.zones.copy') }}'"></span>
                                        </button>
                                    </div>
                                    <input readonly :value="b.code" onclick="this.select()" dir="ltr" class="sk-value">
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ===== RECENTLY UPLOADED (files + images + PDF) ===== --}}
        @php
            $statusMeta = [
                'active'   => ['ar' => 'نشط', 'en' => 'Active', 'cls' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300', 'dot' => 'bg-green-500'],
                'disabled' => ['ar' => 'موقوف', 'en' => 'Stopped', 'cls' => 'bg-gray-100 text-gray-600 dark:bg-gray-700/40 dark:text-gray-300', 'dot' => 'bg-gray-400'],
                'expired'  => ['ar' => 'منتهٍ', 'en' => 'Expired', 'cls' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', 'dot' => 'bg-red-500'],
            ];
        @endphp
        <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm overflow-hidden" wire:poll.10s>
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-white/10">
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                    <i class="fa-solid fa-clock-rotate-left text-primary-600"></i> {{ __('upload.center.recent') }}
                </h3>
                @if (\Illuminate\Support\Facades\Route::has('filament.'.\Filament\Facades\Filament::getCurrentPanel()->getId().'.resources.assets.index'))
                    <a href="{{ route('filament.'.\Filament\Facades\Filament::getCurrentPanel()->getId().'.resources.assets.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-600 transition hover:bg-primary-100 dark:bg-primary-500/10">
                        {{ __('asset_admin.nav') }}
                        <span class="rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] leading-none text-white" dir="ltr">{{ number_format($stats['total']) }}</span>
                    </a>
                @else
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500 dark:bg-white/10" dir="ltr">{{ number_format($stats['total']) }}</span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3 text-start font-medium">{{ __('upload.table.file') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('asset_admin.kind') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('upload.table.size') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('upload.table.status') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('asset_admin.downloads') }}</th>
                            <th class="px-5 py-3 text-start font-medium">{{ __('upload.table.when') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($assets as $a)
                            @php
                                $ext = strtolower(pathinfo($a->original_name, PATHINFO_EXTENSION));
                                $icon = match (true) {
                                    $a->isPdf() => 'fa-solid fa-file-pdf text-red-500',
                                    in_array($ext, ['zip','rar','7z','tar','gz','tgz','bz2','xz']) => 'fa-solid fa-file-zipper text-amber-500',
                                    in_array($ext, ['exe','msi','dmg','pkg','deb','rpm','appimage']) => 'fa-solid fa-window-maximize text-sky-500',
                                    in_array($ext, ['apk','aab','ipa']) => 'fa-solid fa-mobile-screen text-green-500',
                                    in_array($ext, ['php','js','ts','py','rb','go','rs','java','jar','sql']) => 'fa-solid fa-file-code text-indigo-500',
                                    default => 'fa-solid fa-file text-gray-400',
                                };
                                $m = $statusMeta[$a->statusKey()];
                            @endphp
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-white/5 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($a->isImage() && $a->thumbUrl())
                                            <img src="{{ $a->thumbUrl() }}" alt="" class="h-9 w-9 rounded-lg object-cover border border-gray-100 dark:border-white/10">
                                        @else
                                            <i class="{{ $icon }} text-lg w-9 text-center"></i>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate max-w-[220px]">{{ $a->original_name }}</div>
                                            <div class="text-[11px] text-gray-400 font-mono truncate max-w-[220px]" dir="ltr">/d/{{ $a->slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-md bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-semibold uppercase text-gray-600 dark:text-gray-300">{{ __('asset_admin.kind_'.$a->kind) }}</span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap" dir="ltr">{{ $fmt((int) $a->size_bytes) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $m['cls'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $m['dot'] }}"></span>
                                        {{ app()->getLocale() === 'ar' ? $m['ar'] : $m['en'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300" dir="ltr">{{ number_format($a->downloads_count) }}</td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $a->created_at?->diffForHumans() }}</td>
                                <td class="px-5 py-3 text-end">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" x-data="{ c: false }"
                                                @click="window.fnoonCopy('{{ $a->downloadUrl() }}'); c = true; setTimeout(() => c = false, 1500)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300"
                                                :title="c ? '{{ __('asset_admin.action.copied') }}' : '{{ __('asset_admin.action.copy_link') }}'">
                                            <i class="fa-solid text-[10px]" :class="c ? 'fa-check text-green-600' : 'fa-link'"></i>
                                            <span x-text="c ? '{{ __('upload.zones.copied') }}' : '{{ __('asset_admin.action.copy_link') }}'"></span>
                                        </button>
                                        <a href="{{ $a->pageUrl() }}" target="_blank"
                                           class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-600 hover:bg-primary-100 dark:bg-primary-500/10">
                                            <i class="fa-solid fa-up-right-from-square text-[10px]"></i> {{ __('upload.zones.open_page') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-300 dark:text-gray-600"></i>
                                    <p class="mt-3 text-gray-400">{{ __('upload.center.empty') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Uppy assets (CDN, no build step) ===== --}}
    @push('styles')
        <link href="https://releases.transloadit.com/uppy/v3.27.0/uppy.min.css" rel="stylesheet">
        <style>
            /* Self-contained layout (Filament's compiled CSS lacks some grid utilities) */
            .fc-grid4 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
            @media (min-width: 1024px) { .fc-grid4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .fc-grid3 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
            @media (min-width: 1280px) { .fc-grid3 { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
            .fc-grid2 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
            @media (min-width: 1024px) { .fc-grid2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .fc-stack > * + * { margin-top: 1rem; }
            [x-cloak] { display: none !important; }
            /* .sk-* share-kit styles are defined panel-wide in filament/upload/chrome-styles */
            .sk-codes { margin-top: .95rem; }

            /* ===== Hero header ===== */
            .fc-hero {
                position: relative; overflow: hidden; border-radius: 1.5rem;
                padding: 1.75rem; color: #fff;
                background: linear-gradient(125deg, #007a3c 0%, #006C35 45%, #00472a 100%);
                box-shadow: 0 22px 45px -28px rgba(0,108,53,.7);
            }
            .fc-hero-blob { position: absolute; border-radius: 9999px; background: rgba(255,255,255,.06); pointer-events: none; }
            .fc-hero-blob--a { width: 16rem; height: 16rem; top: -7rem; inset-inline-end: -4rem; }
            .fc-hero-blob--b { width: 12rem; height: 12rem; bottom: -6rem; inset-inline-start: 20%; background: rgba(201,169,97,.10); }
            .fc-hero-icon {
                display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;
                width: 3.5rem; height: 3.5rem; border-radius: 1rem;
                background: rgba(255,255,255,.15); color: #fff; backdrop-filter: blur(2px);
            }
            .fc-hero-pill {
                display: inline-flex; align-items: center; gap: .4rem;
                padding: .4rem .8rem; border-radius: 9999px;
                font-size: .75rem; font-weight: 700; white-space: nowrap;
            }
            .fc-hero-pill.is-green { background: rgba(255,255,255,.15); color: #fff; }
            .fc-hero-pill.is-amber { background: rgba(245,158,11,.22); color: #fde68a; }
            .fc-hero-pill.is-white { background: #fff; color: #006C35; }

            /* ===== Stat cards ===== */
            .fc-stat {
                position: relative; overflow: hidden;
                display: flex; align-items: center; gap: 1rem; padding: 1.2rem;
                border: 1px solid #eef0f2; border-radius: 1.25rem; background: #fff;
                transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            }
            /* coloured top accent per card (uses the --accent CSS var) */
            .fc-stat::before {
                content: ''; position: absolute; top: 0; inset-inline: 0; height: 3px;
                background: var(--accent, #006C35);
            }
            .dark .fc-stat { background: #0b1220; border-color: rgba(255,255,255,.08); }
            .fc-stat:hover {
                transform: translateY(-4px);
                box-shadow: 0 18px 34px -20px rgba(0,108,53,.4);
                border-color: var(--accent-soft, rgba(0,108,53,.2));
            }
            .fc-stat-icon {
                display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;
                width: 3rem; height: 3rem; border-radius: .9rem;
                background: var(--accent-soft, rgba(0,108,53,.1));
                color: var(--accent, #006C35);
                transition: transform .22s ease;
            }
            .fc-stat:hover .fc-stat-icon { transform: scale(1.1) rotate(-5deg); }
            .uppy-Root, .uppy-Dashboard, .uppy-Dashboard--width-md { width: 100% !important; max-width: 100% !important; }

            /* Brand Uppy with the Saudi-green palette */
            .uppy-Dashboard-inner { border-radius: 1rem; border: 1.5px dashed rgba(0,108,53,.25); background: #fbfdfb; }
            .dark .uppy-Dashboard-inner { background: #111827; border-color: rgba(201,169,97,.25); }
            .uppy-Dashboard-AddFiles { border: none; }
            .uppy-Dashboard-browse { color: #006C35; font-weight: 700; }
            .uppy-Dashboard-browse:hover { border-bottom-color: #006C35; }
            .uppy-StatusBar-actionBtn--upload,
            .uppy-Dashboard-Item-action--remove { background-color: #006C35; }
            .uppy-StatusBar.is-uploading .uppy-StatusBar-progress,
            .uppy-StatusBar-progress { background-color: #006C35; }
            .uppy-StatusBar.is-complete .uppy-StatusBar-statusPrimary { color: #006C35; }
            .uppy-Dashboard-Item-progressIndicator .uppy-c-icon { fill: #006C35; }
            .uppy-Dashboard-AddFiles-title { color: #4b5563; font-weight: 600; }
            .dark .uppy-Dashboard-AddFiles-title { color: #cbd5e1; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://releases.transloadit.com/uppy/v3.27.0/uppy.min.js"></script>
        <script>
            const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Arabic UI strings for Uppy (null → keep the default English).
            const FNOON_LOCALE = @js(app()->getLocale() === 'ar' ? [
                'strings' => [
                    'dropPasteFiles' => 'أفلت الملفات هنا أو %{browseFiles}',
                    'browseFiles' => 'تصفّح الملفات',
                    'addMoreFiles' => 'إضافة ملفات',
                    'addingMoreFiles' => 'جارٍ الإضافة…',
                    'upload' => 'رفع',
                    'cancel' => 'إلغاء',
                    'done' => 'تم',
                    'back' => 'رجوع',
                    'removeFile' => 'إزالة الملف',
                    'editFile' => 'تعديل الملف',
                    'myDevice' => 'جهازي',
                    'dropHint' => 'أفلت ملفاتك هنا',
                    'uploading' => 'جارٍ الرفع',
                    'complete' => 'مكتمل',
                    'uploadComplete' => 'اكتمل الرفع',
                    'uploadFailed' => 'فشل الرفع',
                    'retryUpload' => 'إعادة المحاولة',
                    'pauseUpload' => 'إيقاف مؤقّت',
                    'resumeUpload' => 'استئناف',
                    'pleaseWait' => 'يرجى الانتظار…',
                    'loading' => 'جارٍ التحميل…',
                    'dataUploadedOfTotal' => '%{complete} من %{total}',
                    'xTimeLeft' => 'بقي %{time}',
                    'uploadXFiles' => ['0' => 'رفع %{smart_count} ملف', '1' => 'رفع %{smart_count} ملفات'],
                    'uploadXNewFiles' => ['0' => 'رفع +%{smart_count} ملف', '1' => 'رفع +%{smart_count} ملفات'],
                    'xFilesSelected' => ['0' => 'تم اختيار %{smart_count} ملف', '1' => 'تم اختيار %{smart_count} ملفات'],
                ],
            ] : null);

            function uppyOptions(extra) {
                return Object.assign({}, extra, FNOON_LOCALE ? { locale: FNOON_LOCALE } : {});
            }

            // Shared store so both uploaders feed the same "ready to share" panel.
            document.addEventListener('alpine:init', () => {
                Alpine.store('share', { items: [] });
            });
            function pushAsset(a) {
                if (a && a.slug && window.Alpine) Alpine.store('share').items.unshift(a);
            }
            function refreshTable() { if (window.Livewire) window.Livewire.dispatch('$refresh'); }

            // ===== Zone A — files & archives (resumable multipart) =====
            function fnoonFiles() {
                return {
                    init() {
                        const el = document.getElementById('fnoon-files');
                        if (!el || el.dataset.mounted) return;   // guard against double-mount
                        el.dataset.mounted = '1';

                        const post = async (url, body) => {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                                body: JSON.stringify(body),
                            });
                            if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || ('Upload error ' + res.status)); }
                            return res.json();
                        };

                        new Uppy.Uppy(uppyOptions({ autoProceed: false, restrictions: { maxNumberOfFiles: 5, maxFileSize: {{ $maxBytes }} } }))
                            .use(Uppy.Dashboard, { inline: true, target: '#fnoon-files', height: 320, proudlyDisplayPoweredByUppy: false, note: '{{ __('upload.center.zone_hint') }}' })
                            .use(Uppy.AwsS3Multipart, {
                                limit: 6,
                                getChunkSize: () => {{ $partSize }},
                                createMultipartUpload: async (file) => {
                                    const data = await post('{{ route('upload.multipart.create') }}', { filename: file.name, type: file.type, size: file.size });
                                    file.meta.sessionUuid = data.sessionUuid;
                                    return { uploadId: data.uploadId, key: data.key };
                                },
                                signPart: async (file, { uploadId, key, partNumber }) => {
                                    const data = await post('{{ route('upload.multipart.sign') }}', { key, uploadId, partNumber });
                                    return { url: data.url };
                                },
                                completeMultipartUpload: async (file, { uploadId, key, parts }) => {
                                    const data = await post('{{ route('upload.multipart.complete') }}', { sessionUuid: file.meta.sessionUuid, key, uploadId, parts });
                                    pushAsset(data.asset);
                                    return { location: data.location };
                                },
                                abortMultipartUpload: async (file, { uploadId, key }) => {
                                    await post('{{ route('upload.multipart.abort') }}', { sessionUuid: file.meta.sessionUuid, key, uploadId });
                                },
                            })
                            .on('complete', refreshTable);
                    },
                };
            }

            // ===== Zone B — images & PDF (direct, with share kit) =====
            function fnoonMedia() {
                return {
                    init() {
                        const el = document.getElementById('fnoon-media');
                        if (!el || el.dataset.mounted) return;   // guard against double-mount
                        el.dataset.mounted = '1';

                        new Uppy.Uppy(uppyOptions({
                            autoProceed: true,
                            restrictions: { maxNumberOfFiles: 10, maxFileSize: {{ (int) env('MEDIA_MAX_KB', 51200) }} * 1024, allowedFileTypes: ['image/*', '.pdf'] },
                        }))
                            .use(Uppy.Dashboard, { inline: true, target: '#fnoon-media', height: 320, proudlyDisplayPoweredByUppy: false, note: 'PNG · JPG · WEBP · GIF · SVG · PDF' })
                            .use(Uppy.XHRUpload, {
                                endpoint: '{{ route('upload.media') }}',
                                fieldName: 'file',
                                formData: true,
                                limit: 4,
                                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                            })
                            .on('upload-success', (file, response) => { pushAsset(response.body); })
                            .on('complete', refreshTable);
                    },
                };
            }

            // ===== Share-kit results panel =====
            function shareResults() {
                return {
                    get items() { return this.$store.share.items; },
                    copiedKey: null,
                    copy(text, key) {
                        window.fnoonCopy(text).then(() => {
                            this.copiedKey = key;
                            setTimeout(() => { if (this.copiedKey === key) this.copiedKey = null; }, 1500);
                        });
                    },
                    clear() { this.$store.share.items = []; },
                    human(b) {
                        b = +b || 0; if (b <= 0) return '';
                        const u = ['B', 'KB', 'MB', 'GB', 'TB'];
                        const i = Math.floor(Math.log(b) / Math.log(1024));
                        return (b / Math.pow(1024, i)).toFixed(1) + ' ' + u[Math.min(i, 4)];
                    },
                    icon(k) {
                        return ({
                            page: 'fa-solid fa-link', direct: 'fa-solid fa-link',
                            html: 'fa-solid fa-code', markdown: 'fa-brands fa-markdown',
                            bbcode: 'fa-solid fa-puzzle-piece', thumb: 'fa-solid fa-image',
                            forum: 'fa-solid fa-comments',
                        })[k] || 'fa-solid fa-code';
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
