@extends('layouts.app')

@section('title', $software->name)
@section('meta_description', $software->short_description)
@section('og_title', $software->name)
@section('og_description', $software->short_description)
@section('og_image', $software->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) : '')

@push('jsonld')
    <script type="application/ld+json">{!! json_encode($software->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@php
    $dev = $software->developer;
    $apk = $software->downloadLinks->first();
    $shots = $software->screenshots->map(fn ($s) => ['src' => \Illuminate\Support\Facades\Storage::disk('public')->url($s->path), 'cap' => (string) $s->caption])->values();
@endphp

<div x-data="{ run: false, lb: false, i: 0, imgs: @js($shots),
        open(idx){ this.i = idx; this.lb = true },
        next(){ if (this.imgs.length) this.i = (this.i + 1) % this.imgs.length },
        prev(){ if (this.imgs.length) this.i = (this.i - 1 + this.imgs.length) % this.imgs.length },
        get cur(){ return this.imgs[this.i] || { src:'', cap:'' } } }">

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-saudi-green to-[#00481f] text-white">
        <div class="absolute inset-0 hero-grid opacity-20"></div>
        <div class="absolute -top-24 -end-24 h-96 w-96 rounded-full bg-royal-gold/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -start-24 h-[28rem] w-[28rem] rounded-full bg-white/5 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 lg:grid-cols-2 lg:py-16">
            {{-- left: app info --}}
            <div>
                <nav class="mb-5 flex items-center gap-2 text-xs text-white/60" aria-label="breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-white">{{ __('site.nav.home') }}</a>
                    <span>/</span>
                    <a href="{{ route('browse', ['type' => 'mobile_app']) }}" class="hover:text-white">{{ __('content.type.mobile_app') }}</a>
                </nav>

                <div class="flex items-center gap-4">
                    @if ($software->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                             alt="{{ $software->name }}" width="96" height="96"
                             class="h-20 w-20 rounded-3xl bg-white/10 object-contain p-1 ring-1 ring-white/20 shadow-xl">
                    @else
                        <span class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-white/10 text-3xl ring-1 ring-white/20"><i class="fa-solid fa-mobile-screen-button"></i></span>
                    @endif
                    <div class="min-w-0">
                        <h1 class="font-cairo text-2xl font-black leading-tight md:text-3xl">{{ $software->name }}</h1>
                        @if ($dev)
                            <p class="mt-1 text-sm text-white/75">
                                {{ __('site.app.by') }}
                                <a href="#developer" class="font-bold text-royal-gold hover:underline">{{ $dev->name }}</a>
                                @if ($dev->is_verified)<i class="fa-solid fa-circle-check text-royal-gold" title="{{ __('developer.verified') }}"></i>@endif
                            </p>
                        @endif
                    </div>
                </div>

                {{-- meta chips --}}
                <div class="mt-5 flex flex-wrap items-center gap-2 text-sm">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1" dir="ltr">
                        <i class="fa-solid fa-star text-royal-gold"></i> {{ number_format($software->rating_avg, 1) }}
                        <span class="text-white/50">({{ $software->reviews_count }})</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1" dir="ltr">
                        <i class="fa-solid fa-arrow-down-long"></i> {{ number_format($software->downloads_count) }}
                    </span>
                    @if ($software->current_version)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1" dir="ltr">v{{ $software->current_version }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-royal-gold/90 px-3 py-1 font-bold text-luxury-black">
                        <i class="fa-solid fa-gift"></i> {{ __('content.license.free') }}
                    </span>
                </div>

                <p class="mt-5 max-w-xl text-white/85 leading-relaxed">{{ $software->short_description }}</p>

                {{-- CTAs --}}
                <div class="mt-7 flex flex-wrap gap-3">
                    @if ($software->hasLivePreview())
                        <button type="button" @click="run = true; document.getElementById('app-preview').scrollIntoView({behavior:'smooth', block:'center'})"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 font-cairo font-bold text-saudi-green shadow-lg transition hover:-translate-y-0.5">
                            <i class="fa-solid fa-play"></i> {{ __('site.live_preview.play') }}
                        </button>
                    @endif
                    @if ($apk)
                        <a href="{{ route('download.gateway', [$software, $apk]) }}" data-dl-all
                           class="inline-flex items-center gap-2 rounded-xl bg-royal-gold px-5 py-3 font-cairo font-bold text-luxury-black shadow-lg transition hover:-translate-y-0.5">
                            <i class="fa-brands fa-android"></i> {{ __('site.app.download_apk') }}
                        </a>
                    @endif
                </div>

                {{-- store badges --}}
                @if ($software->hasStoreLinks())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($software->play_url)
                            <a href="{{ $software->play_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-black/30 px-4 py-2 ring-1 ring-white/15 transition hover:bg-black/40">
                                <i class="fa-brands fa-google-play text-emerald-400"></i><span class="text-start text-xs leading-tight"><span class="block text-white/60">{{ __('site.stores.get_on') }}</span><span class="block font-bold">Google Play</span></span>
                            </a>
                        @endif
                        @if ($software->appstore_url)
                            <a href="{{ $software->appstore_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-black/30 px-4 py-2 ring-1 ring-white/15 transition hover:bg-black/40">
                                <i class="fa-brands fa-apple text-lg"></i><span class="text-start text-xs leading-tight"><span class="block text-white/60">{{ __('site.stores.download_on') }}</span><span class="block font-bold">App Store</span></span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- right: live phone preview --}}
            <div id="app-preview" class="flex flex-col items-center scroll-mt-24">
                @if ($software->hasLivePreview())
                    <div class="relative w-[300px] max-w-full">
                        <div class="relative overflow-hidden rounded-[2.6rem] border-[11px] border-black bg-black shadow-2xl" style="aspect-ratio: 9 / 19.5;">
                            <div class="absolute left-1/2 top-0 z-20 h-5 w-28 -translate-x-1/2 rounded-b-2xl bg-black"></div>
                            <button type="button" x-show="!run" @click="run = true"
                                    class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-[1.7rem] bg-gradient-to-br from-saudi-green to-saudi-green-dark text-white">
                                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-2xl"><i class="fa-solid fa-play ms-1"></i></span>
                                <span class="font-cairo font-bold">{{ __('site.live_preview.play') }}</span>
                            </button>
                            <template x-if="run">
                                <iframe src="{{ $software->livePreviewSrc() }}" loading="lazy" scrolling="no"
                                        class="absolute inset-0 h-full w-full rounded-[1.7rem] bg-white" style="overflow:hidden;"
                                        title="{{ $software->name }}" allow="fullscreen; clipboard-write; accelerometer; gyroscope"></iframe>
                            </template>
                        </div>
                    </div>

                    @if ($software->hasPreviewCredentials())
                        <div class="mt-4 w-[300px] max-w-full rounded-2xl bg-white/10 p-3 text-sm ring-1 ring-white/15">
                            <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-royal-gold"><i class="fa-solid fa-circle-info"></i> {{ __('site.live_preview.demo_login') }}</p>
                            @if ($software->preview_username)
                                <div class="flex items-center gap-2 py-0.5"><span class="w-20 shrink-0 text-xs text-white/60">{{ __('site.live_preview.username') }}</span><code dir="ltr" class="min-w-0 flex-1 truncate rounded bg-black/30 px-2 py-1 text-xs">{{ $software->preview_username }}</code><button type="button" x-data="{c:false}" @click="window.fnoonCopy(@js($software->preview_username)); c=true; setTimeout(()=>c=false,1500)" class="text-white/70 hover:text-white"><i class="fa-solid" :class="c?'fa-check':'fa-copy'"></i></button></div>
                            @endif
                            @if ($software->preview_password)
                                <div class="flex items-center gap-2 py-0.5"><span class="w-20 shrink-0 text-xs text-white/60">{{ __('site.live_preview.password') }}</span><code dir="ltr" class="min-w-0 flex-1 truncate rounded bg-black/30 px-2 py-1 text-xs">{{ $software->preview_password }}</code><button type="button" x-data="{c:false}" @click="window.fnoonCopy(@js($software->preview_password)); c=true; setTimeout(()=>c=false,1500)" class="text-white/70 hover:text-white"><i class="fa-solid" :class="c?'fa-check':'fa-copy'"></i></button></div>
                            @endif
                        </div>
                    @endif
                @else
                    {{-- no preview: show the big icon as a poster --}}
                    @if ($software->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}" alt="{{ $software->name }}" class="h-48 w-48 rounded-[2rem] bg-white/10 object-contain p-3 shadow-2xl ring-1 ring-white/20">
                    @endif
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== BODY ===================== --}}
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 lg:grid-cols-3">
        <div class="space-y-10 lg:col-span-2">

            {{-- screenshots --}}
            @if ($software->screenshots->isNotEmpty())
                <section>
                    <h2 class="mb-4 font-cairo text-xl font-black text-luxury-black"><i class="fa-solid fa-images text-saudi-green"></i> {{ __('site.screenshots') }}</h2>
                    <div class="-mx-1 flex snap-x gap-4 overflow-x-auto px-1 pb-3">
                        @foreach ($software->screenshots as $idx => $shot)
                            <button type="button" @click="open({{ $idx }})" class="group relative shrink-0 snap-start overflow-hidden rounded-[1.6rem] border-[6px] border-luxury-black bg-luxury-black shadow-lg" style="width: 190px; aspect-ratio: 9/19;">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($shot->path) }}" alt="{{ $shot->caption }}" loading="lazy" class="h-full w-full rounded-[1.1rem] object-cover transition group-hover:scale-105">
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- features --}}
            @if (! empty($software->features))
                <section>
                    <h2 class="mb-4 font-cairo text-xl font-black text-luxury-black"><i class="fa-solid fa-sparkles text-saudi-green"></i> {{ __('software.section.features') }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($software->features as $feature)
                            @php($txt = is_array($feature) ? ($feature[app()->getLocale()] ?? $feature['ar'] ?? $feature['en'] ?? '') : $feature)
                            @if ($txt)
                                <div class="flex items-start gap-3 rounded-xl border border-royal-gold/10 bg-white p-3.5">
                                    <i class="fa-solid fa-circle-check mt-0.5 text-saudi-green"></i>
                                    <span class="text-sm text-gray-700">{{ $txt }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- description --}}
            @if ($software->description)
                <section class="card-luxury p-7 prose max-w-none">
                    <h2 class="font-cairo">{{ __('site.about') }}</h2>
                    {!! $software->description !!}
                </section>
            @endif
        </div>

        {{-- aside --}}
        <aside class="space-y-6">
            {{-- developer card --}}
            @if ($dev)
                <div id="developer" class="card-luxury scroll-mt-24 p-6">
                    <div class="flex items-center gap-4">
                        @if ($dev->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dev->logo) }}" alt="{{ $dev->name }}" class="h-16 w-16 rounded-2xl bg-white object-contain ring-1 ring-royal-gold/15">
                        @else
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-saudi-green/10 text-2xl text-saudi-green"><i class="fa-solid fa-code"></i></span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">{{ __('site.app.developer') }}</p>
                            <h3 class="truncate font-cairo text-lg font-black text-luxury-black">{{ $dev->name }}
                                @if ($dev->is_verified)<i class="fa-solid fa-circle-check text-saudi-green text-sm" title="{{ __('developer.verified') }}"></i>@endif
                            </h3>
                        </div>
                    </div>
                    @if ($dev->description)
                        <p class="mt-3 text-sm leading-relaxed text-gray-500">{{ $dev->description }}</p>
                    @endif

                    @if ($dev->hasContact())
                        <p class="mt-4 mb-2 text-xs font-bold text-gray-500">{{ __('site.app.contact_dev') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @if ($dev->whatsappUrl())
                                <a href="{{ $dev->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-3.5 py-2 text-sm font-bold text-white transition hover:-translate-y-0.5"><i class="fa-brands fa-whatsapp"></i> {{ __('site.app.whatsapp') }}</a>
                            @endif
                            @if ($dev->email)
                                <a href="mailto:{{ $dev->email }}" class="inline-flex items-center gap-2 rounded-xl bg-saudi-green px-3.5 py-2 text-sm font-bold text-white transition hover:-translate-y-0.5"><i class="fa-solid fa-envelope"></i> {{ __('site.app.email') }}</a>
                            @endif
                            @if ($dev->website)
                                <a href="{{ $dev->website }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-3.5 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-200"><i class="fa-solid fa-globe"></i> {{ __('site.app.website') }}</a>
                            @endif
                            @if ($dev->twitterUrl())
                                <a href="{{ $dev->twitterUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-luxury-black px-3.5 py-2 text-sm font-bold text-white transition hover:-translate-y-0.5"><i class="fa-brands fa-x-twitter"></i> X</a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- download / install card --}}
            <div class="card-luxury p-6">
                <h3 class="mb-4 flex items-center gap-2 font-cairo text-base font-black text-luxury-black"><i class="fa-solid fa-cloud-arrow-down text-saudi-green"></i> {{ __('site.app.get_app') }}</h3>

                @if ($apk)
                    <a href="{{ route('download.gateway', [$software, $apk]) }}" data-dl-all
                       class="btn-primary w-full justify-center text-base shadow-lg shadow-saudi-green/20">
                        <i class="fa-brands fa-android"></i> {{ __('site.app.download_apk') }}
                    </a>
                    @if ($apk->humanSize())
                        <p class="mt-2 text-center text-xs text-gray-400" dir="ltr">APK · {{ $apk->humanSize() }}</p>
                    @endif
                @endif

                @if ($software->hasStoreLinks())
                    <div class="mt-3 flex flex-col gap-2">
                        @if ($software->play_url)
                            <a href="{{ $software->play_url }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl bg-luxury-black px-4 py-2.5 text-white transition hover:opacity-90"><i class="fa-brands fa-google-play text-xl text-emerald-400"></i><span class="text-start leading-tight"><span class="block text-[10px] text-white/60">{{ __('site.stores.get_on') }}</span><span class="block font-bold">Google Play</span></span></a>
                        @endif
                        @if ($software->appstore_url)
                            <a href="{{ $software->appstore_url }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl bg-luxury-black px-4 py-2.5 text-white transition hover:opacity-90"><i class="fa-brands fa-apple text-2xl"></i><span class="text-start leading-tight"><span class="block text-[10px] text-white/60">{{ __('site.stores.download_on') }}</span><span class="block font-bold">App Store</span></span></a>
                        @endif
                    </div>
                @endif

                {{-- QR --}}
                @if ($software->qr_enabled && $software->hasStoreLinks())
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <p class="mb-3 text-center text-xs font-bold text-gray-500">{{ __('site.qr.title') }}</p>
                        <div class="flex flex-wrap justify-center gap-4">
                            @if ($software->play_url)<div class="text-center"><div class="fnoon-qr inline-block rounded-xl border border-gray-100 p-1.5" data-qr="{{ $software->play_url }}"></div><span class="mt-1 block text-[11px] font-bold text-gray-500"><i class="fa-brands fa-android text-emerald-500"></i> Android</span></div>@endif
                            @if ($software->appstore_url)<div class="text-center"><div class="fnoon-qr inline-block rounded-xl border border-gray-100 p-1.5" data-qr="{{ $software->appstore_url }}"></div><span class="mt-1 block text-[11px] font-bold text-gray-500"><i class="fa-brands fa-apple"></i> iOS</span></div>@endif
                        </div>
                    </div>
                    @once
                        @push('scripts')
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
                            <script>
                                (function () {
                                    function render() {
                                        if (typeof QRCode === 'undefined') { return setTimeout(render, 150); }
                                        document.querySelectorAll('.fnoon-qr[data-qr]').forEach(function (el) {
                                            if (el.dataset.done) return; el.dataset.done = '1';
                                            new QRCode(el, { text: el.getAttribute('data-qr'), width: 110, height: 110, colorDark: '#0a0a0a', colorLight: '#fff', correctLevel: QRCode.CorrectLevel.M });
                                        });
                                    }
                                    render();
                                })();
                            </script>
                        @endpush
                    @endonce
                @endif

            </div>

            <x-share :title="$software->name" :url="route('software.show', $software)" />

            <x-ad placement="sidebar" />
        </aside>
    </div>

    {{-- related apps --}}
    @if ($related->isNotEmpty())
        <div class="mx-auto max-w-7xl px-4 pb-12">
            @include('partials.software-list', ['title' => __('site.app.more_apps'), 'items' => $related])
        </div>
    @endif

    {{-- screenshot lightbox --}}
    <div x-show="lb" x-cloak style="display:none" x-transition.opacity
         @keydown.escape.window="lb=false" @keydown.arrow-right.window="prev()" @keydown.arrow-left.window="next()"
         @click="lb=false" class="fixed inset-0 z-[80] flex select-none items-center justify-center bg-black/90 p-4">
        <button @click.stop="prev()" class="absolute start-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"><i class="fa-solid fa-chevron-right rtl:rotate-180"></i></button>
        <img :src="cur.src" :alt="cur.cap" @click.stop class="max-h-[85vh] max-w-[90vw] rounded-2xl object-contain shadow-2xl">
        <button @click.stop="next()" class="absolute end-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"><i class="fa-solid fa-chevron-left rtl:rotate-180"></i></button>
        <button @click="lb=false" class="absolute end-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"><i class="fa-solid fa-xmark"></i></button>
    </div>

    {{-- record download to history --}}
    @php($dlData = ['slug' => $software->slug, 'name' => (string) $software->name, 'icon' => $software->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) : null, 'dev' => optional($software->developer)->name])
    @push('scripts')
        <script>
            (function () {
                const dl = @json($dlData);
                document.addEventListener('click', function (e) {
                    if (!e.target.closest('a[href*="/download/"], a[href*="/go/"], [data-dl-all]')) return;
                    try {
                        const k = 'fnoon_downloads'; let arr = JSON.parse(localStorage.getItem(k) || '[]');
                        if (!Array.isArray(arr)) arr = [];
                        arr = arr.filter(x => x && x.slug !== dl.slug);
                        arr.unshift(Object.assign({}, dl, { at: Date.now() }));
                        localStorage.setItem(k, JSON.stringify(arr.slice(0, 60)));
                    } catch (e) {}
                }, true);
            })();
        </script>
    @endpush
</div>
@endsection
