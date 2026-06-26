@extends('layouts.app')

@section('title', __('site.hero.title'))

@push('jsonld')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => \App\Models\Setting::text('site_name', config('app.name')),
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@php
    $typeMeta = [
        'application' => ['grad' => 'from-emerald-500 to-green-700'],
        'script'      => ['grad' => 'from-sky-500 to-indigo-700'],
        'template'    => ['grad' => 'from-amber-400 to-orange-600'],
        'mobile_app'  => ['grad' => 'from-cyan-500 to-blue-700'],
        'plugin'      => ['grad' => 'from-fuchsia-500 to-purple-700'],
    ];
@endphp

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="relative hero-pattern text-white" style="z-index: 20;">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 hero-grid opacity-60"></div>
            <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-royal-gold/10 blur-3xl float-anim"></div>
            <div class="absolute -bottom-32 -start-24 w-[28rem] h-[28rem] rounded-full bg-saudi-green/20 blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 text-center" style="padding-top: clamp(2.25rem, 5.5vw, 3.75rem); padding-bottom: clamp(2.25rem, 5.5vw, 3.75rem);">
            <span class="chip mx-auto mb-4">
                <i class="fa-solid fa-globe text-royal-gold"></i> {{ __('site.hero.badge') }}
            </span>

            <h1 class="font-cairo font-black text-4xl md:text-5xl leading-[1.15] max-w-4xl mx-auto">
                {{ \App\Models\Setting::text('hero_title', __('site.hero.title')) }}
            </h1>
            <p class="mt-3 text-gray-300 max-w-2xl mx-auto" style="font-size: 1rem;">{{ \App\Models\Setting::text('hero_subtitle', __('site.hero.subtitle')) }}</p>

            {{-- Search --}}
            <div x-data="heroSearch()" class="mt-9 max-w-2xl mx-auto relative">
                <form action="{{ route('search') }}" method="GET" class="hero-glass rounded-2xl p-2 flex gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute top-1/2 -translate-y-1/2 ms-4 text-gray-400"></i>
                        <input name="q" x-model="q" @input.debounce.300ms="fetchResults()" autocomplete="off"
                               placeholder="{{ __('site.hero.search_placeholder') }}"
                               class="w-full bg-transparent border-0 ps-11 pe-4 py-3.5 text-white placeholder-gray-400 focus:outline-none focus:ring-0">
                    </div>
                    <button class="btn-gold px-7 shrink-0">{{ __('site.hero.search_button') }}</button>
                </form>

                {{-- Live results: kept OUTSIDE the backdrop-filter form so it never clips at the hero edge --}}
                <div x-show="results.length" x-cloak @click.outside="results = []"
                     class="absolute mt-3 w-full bg-white rounded-xl shadow-2xl text-start" style="z-index: 50; max-height: 24rem; overflow-y: auto;">
                    <template x-for="r in results" :key="r.slug">
                        <a :href="r.url" class="flex items-center gap-3 px-4 py-3 hover:bg-royal-gold/10 text-luxury-black border-b border-gray-50 last:border-0">
                            <i class="fa-solid fa-cube text-saudi-green"></i>
                            <span x-text="r.name" class="font-medium"></span>
                        </a>
                    </template>
                </div>
            </div>

            {{-- Trust chips --}}
            <div class="mt-6 flex items-center justify-center gap-3 flex-wrap">
                <span class="chip"><i class="fa-solid fa-circle-check text-green-400"></i> {{ __('site.hero.trust.verified') }}</span>
                <span class="chip"><i class="fa-solid fa-shield-halved text-green-400"></i> {{ __('site.hero.trust.malware') }}</span>
                <span class="chip"><i class="fa-solid fa-database text-royal-gold"></i> {{ __('site.hero.trust.large') }}</span>
                <span class="chip"><i class="fa-solid fa-bolt text-royal-gold"></i> {{ __('site.hero.trust.cdn') }}</span>
            </div>

            {{-- Stats row hidden per request --}}
        </div>

        {{-- bottom wave --}}
        <div class="relative">
            <svg viewBox="0 0 1440 60" class="w-full h-[40px] fill-[#FBFAF6]" preserveAspectRatio="none">
                <path d="M0,32 C480,80 960,0 1440,32 L1440,60 L0,60 Z"></path>
            </svg>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4">

        {{-- ===== BANNERS (managed from admin → Banners, position "home_top") ===== --}}
        @if ($banners->isNotEmpty())
            <section class="pt-8">
                <div class="grid gap-4 {{ $banners->count() > 1 ? 'sm:grid-cols-2' : '' }}">
                    @foreach ($banners as $banner)
                        @php($img = $banner->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($banner->image) : null)
                        <a @if ($banner->link) href="{{ $banner->link }}" @endif
                           class="block overflow-hidden rounded-2xl border border-royal-gold/15 group">
                            @if ($img)
                                <img src="{{ $img }}" alt="{{ $banner->title }}"
                                     class="w-full h-auto object-cover group-hover:scale-[1.02] transition duration-500">
                            @else
                                <div class="p-8 text-center text-white" style="background:linear-gradient(120deg,#006C35,#00582b)">
                                    <span class="font-cairo font-bold text-xl">{{ $banner->title }}</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ===== BROWSE BY TYPE ===== --}}
        <section class="py-12">
            <div class="text-center mb-10">
                <h2 class="font-cairo font-black text-3xl">{{ __('site.browse_by_type') }}</h2>
                <p class="text-gray-500 mt-2">{{ __('site.browse_by_type_sub') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                @foreach ($types as $type)
                    @php($meta = $typeMeta[$type->value] ?? ['grad' => 'from-slate-500 to-gray-700'])
                    <a href="{{ route('browse', ['type' => $type->value]) }}" class="type-card group !p-4 flex flex-col items-center text-center">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $meta['grad'] }} text-white text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                            <i class="{{ $type->icon() }}"></i>
                        </span>
                        <h3 class="font-cairo font-bold text-sm mt-3 leading-tight text-luxury-black group-hover:text-saudi-green">{{ $type->label() }}</h3>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('site.items_count', ['count' => number_format($typeCounts[$type->value] ?? 0)]) }}
                        </p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ===== SPOTLIGHT + WHY MINI ===== --}}
        @if ($spotlight)
            <section class="py-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {{-- Big spotlight (editor's choice — admin-configurable) --}}
                    @php($spotBg = \App\Support\Spotlight::bg())
                    @php($spotOverlay = \App\Support\Spotlight::overlayStyle())
                    @php($spotBadge = \App\Support\Spotlight::badge() ?: __('site.sections.editor_choice'))
                    <a href="{{ route('software.show', $spotlight) }}"
                       class="group relative lg:col-span-2 flex flex-col overflow-hidden rounded-2xl min-h-[280px] text-white shadow-lg">
                        {{-- background layer (image or gradient) --}}
                        <div class="absolute inset-0 transition-transform duration-700 ease-out group-hover:scale-105"
                             style="@if ($spotBg) background-image:url('{{ $spotBg }}'); background-size:cover; background-position:center; @else background:linear-gradient(135deg,#0f2a1c,#1A1A1A); @endif"></div>
                        {{-- dim overlay for legibility --}}
                        <div class="absolute inset-0" style="background: {{ $spotBg ? $spotOverlay : 'linear-gradient(135deg,rgba(15,42,28,.45),rgba(26,26,26,.55))' }};"></div>
                        <div class="absolute inset-0 hero-grid opacity-25"></div>

                        {{-- content --}}
                        <div class="relative z-10 mt-auto w-full p-8">
                            <span class="chip w-max mb-4"><i class="fa-solid fa-award text-royal-gold"></i> {{ $spotBadge }}</span>
                            <div class="flex items-center gap-4">
                                @if ($spotlight->icon)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($spotlight->icon) }}"
                                         width="80" height="80" class="h-20 w-20 rounded-2xl object-cover ring-1 ring-white/20 shadow-lg shrink-0" alt="">
                                @else
                                    <span class="h-20 w-20 rounded-2xl bg-white/10 inline-flex items-center justify-center text-3xl text-royal-gold ring-1 ring-white/20 shrink-0">
                                        <i class="{{ $spotlight->content_type->icon() }}"></i>
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <h3 class="font-cairo font-black text-2xl sm:text-3xl leading-tight group-hover:text-royal-gold transition">{{ $spotlight->name }}</h3>
                                    <p class="text-gray-200/90 text-sm mt-1.5 line-clamp-2 max-w-xl">{{ $spotlight->short_description }}</p>
                                    <div class="flex items-center gap-2 mt-3" dir="ltr">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold backdrop-blur">
                                            <i class="fa-solid fa-download text-royal-gold"></i> {{ number_format($spotlight->downloads_count) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold backdrop-blur">
                                            <i class="fa-solid fa-star text-royal-gold"></i> {{ $spotlight->rating_avg }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span class="mt-5 inline-flex items-center gap-2 rounded-xl bg-royal-gold px-5 py-2.5 text-sm font-bold text-luxury-black transition-all group-hover:gap-3">
                                <i class="fa-solid fa-circle-down"></i> {{ __('site.spotlight_cta') }}
                            </span>
                        </div>
                    </a>

                    {{-- mini feature stack --}}
                    <div class="grid grid-cols-1 gap-4">
                        <div class="card-luxury p-5 flex items-start gap-3">
                            <span class="h-10 w-10 rounded-xl bg-green-100 text-green-700 inline-flex items-center justify-center shrink-0"><i class="fa-solid fa-shield-halved"></i></span>
                            <div><h4 class="font-bold text-sm">{{ __('site.why.verified.title') }}</h4><p class="text-xs text-gray-500 mt-0.5">{{ __('site.why.verified.text') }}</p></div>
                        </div>
                        <div class="card-luxury p-5 flex items-start gap-3">
                            <span class="h-10 w-10 rounded-xl bg-amber-100 text-amber-700 inline-flex items-center justify-center shrink-0"><i class="fa-solid fa-bolt"></i></span>
                            <div><h4 class="font-bold text-sm">{{ __('site.why.fast.title') }}</h4><p class="text-xs text-gray-500 mt-0.5">{{ __('site.why.fast.text') }}</p></div>
                        </div>
                        <div class="card-luxury p-5 flex items-start gap-3">
                            <span class="h-10 w-10 rounded-xl bg-sky-100 text-sky-700 inline-flex items-center justify-center shrink-0"><i class="fa-solid fa-database"></i></span>
                            <div><h4 class="font-bold text-sm">{{ __('site.why.large.title') }}</h4><p class="text-xs text-gray-500 mt-0.5">{{ __('site.why.large.text') }}</p></div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ===== CONTENT ROWS ===== --}}
        @include('partials.software-row', ['title' => __('site.sections.most_downloaded'), 'items' => $mostDownloaded, 'moreUrl' => route('browse', ['sort' => 'downloads'])])
        @include('partials.software-row', ['title' => __('site.sections.recently_added'), 'items' => $recentlyAdded, 'moreUrl' => route('browse', ['sort' => 'recent'])])
        @include('partials.software-row', ['title' => __('site.sections.featured'), 'items' => $featured, 'moreUrl' => route('browse')])
        @if ($mobileApps->isNotEmpty())
            @include('partials.software-row', ['title' => __('content.type.mobile_app'), 'items' => $mobileApps, 'moreUrl' => route('browse', ['type' => 'mobile_app'])])
        @endif

        {{-- ===== CATEGORIES ===== --}}
        @if ($categories->isNotEmpty())
            <section class="py-10">
                <div class="section-head">
                    <h2 class="font-cairo font-bold text-2xl">{{ __('site.sections.categories') }}</h2>
                    <a href="{{ route('browse') }}" class="view-all">{{ __('site.view_all') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-xs"></i></a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach ($categories as $cat)
                        <a href="{{ route('browse', ['category' => $cat->slug]) }}"
                           class="card-luxury p-5 text-center hover:border-royal-gold/40 group">
                            <i class="{{ $cat->icon ?? 'fa-solid fa-folder' }} text-2xl text-saudi-green group-hover:scale-110 transition inline-block"></i>
                            <div class="mt-3 text-sm font-semibold truncate">{{ $cat->name }}</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- ===== WHY US BAND (managed from the admin → Site features) ===== --}}
    @if ($features->isNotEmpty())
        <section class="bg-white border-y border-royal-gold/10 mt-8">
            <div class="max-w-7xl mx-auto px-4 py-14">
                <h2 class="font-cairo font-black text-3xl text-center mb-10">{{ __('site.why.title') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($features as $feature)
                        <div class="text-center">
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-saudi-green/10 text-saudi-green text-2xl mb-4"><i class="{{ $feature->icon }}"></i></span>
                            <h3 class="font-cairo font-bold text-lg">{{ $feature->title }}</h3>
                            <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $feature->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== UPLOADER CTA (member dashboard) ===== --}}
    @if (\App\Models\Setting::get('member_uploads_enabled'))
    <section class="max-w-7xl mx-auto px-4 py-14">
        <div class="relative overflow-hidden rounded-3xl px-8 py-12 text-center text-white"
             style="background: linear-gradient(135deg, #006C35, #00582b);">
            <div class="absolute inset-0 hero-grid opacity-30"></div>
            <div class="relative">
                <h2 class="font-cairo font-black text-2xl md:text-3xl">{{ \App\Models\Setting::text('cta_title', __('site.cta.title')) }}</h2>
                <p class="mt-3 text-green-100 max-w-xl mx-auto">{{ \App\Models\Setting::text('cta_text', __('site.cta.text')) }}</p>
                <a href="/dashboard" class="btn-gold mt-6 justify-center">
                    <i class="fa-solid fa-cloud-arrow-up"></i> {{ __('site.cta.button') }}
                </a>
            </div>
        </div>
    </section>
    @endif
@endsection

@push('scripts')
<script>
    function heroSearch() {
        return {
            q: '',
            results: [],
            async fetchResults() {
                const term = this.q.trim();
                if (term.length < 1) { this.results = []; return; }
                const res = await fetch('{{ route('search.live') }}?q=' + encodeURIComponent(term));
                const data = await res.json();
                this.results = data.results || [];
            },
        };
    }
</script>
@endpush
