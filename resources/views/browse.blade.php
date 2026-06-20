@extends('layouts.app')

@section('title', $activeType?->label() ?? __('site.browse.title'))

@php
    use App\Enums\ContentType;

    $query = request()->query();
    $osList = ['windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux', 'android' => 'Android', 'ios' => 'iOS', 'web' => 'Web'];
    $licenseList = ['free', 'trial', 'open_source', 'paid'];
    $sortList = ['recent' => 'sort_recent', 'downloads' => 'sort_downloads', 'rating' => 'sort_rating', 'name' => 'sort_name'];

    // helper: current querystring without a given key
    $without = fn ($key) => route('browse', collect($query)->except($key)->all());
    // active filter chips
    $activeChips = collect($filters)->filter(fn ($v, $k) => $v !== null && $v !== '' && $k !== 'sort');
@endphp

@section('content')
    {{-- ===== HEADER BAND ===== --}}
    <section class="relative hero-pattern text-white overflow-hidden">
        <div class="absolute inset-0 hero-grid opacity-50"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-10">
            <nav class="text-sm text-gray-400 flex items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-royal-gold">{{ __('site.nav.home') }}</a>
                <i class="fa-solid fa-angle-left rtl:rotate-180 text-xs"></i>
                <span class="text-gray-200">{{ $activeType?->label() ?? __('site.nav.browse') }}</span>
            </nav>

            <div class="mt-3 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <h1 class="font-cairo font-black text-3xl md:text-4xl flex items-center gap-3">
                        @if ($activeType)
                            <i class="{{ $activeType->icon() }} text-royal-gold"></i>
                        @endif
                        {{ $activeType?->label() ?? __('site.browse.title') }}
                    </h1>
                    <p class="text-gray-300 mt-2">{{ __('site.browse.subtitle') }}</p>
                </div>
                <div class="chip">
                    <i class="fa-solid fa-layer-group text-royal-gold"></i>
                    {{ __('site.filters.results', ['count' => number_format($software->total())]) }}
                </div>
            </div>
        </div>
    </section>

    {{-- ===== TYPE TABS ===== --}}
    <div class="bg-white border-b border-royal-gold/15 sticky top-16 z-30">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center gap-1 overflow-x-auto py-2 no-scrollbar">
                <a href="{{ route('browse') }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition {{ ! $activeType ? 'bg-saudi-green text-white' : 'text-gray-600 hover:bg-saudi-green/10' }}">
                    {{ __('site.filters.all_types') }}
                    <span class="opacity-70 text-xs">({{ number_format($totalCount) }})</span>
                </a>
                @foreach ($types as $type)
                    <a href="{{ route('browse', ['type' => $type->value]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition flex items-center gap-2 {{ $activeType === $type ? 'bg-saudi-green text-white' : 'text-gray-600 hover:bg-saudi-green/10' }}">
                        <i class="{{ $type->icon() }} text-xs"></i>
                        {{ $type->label() }}
                        <span class="opacity-70 text-xs">({{ number_format($typeCounts[$type->value] ?? 0) }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ showFilters: false }">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            {{-- ===== SIDEBAR FILTERS ===== --}}
            <aside class="lg:col-span-1">
                {{-- mobile toggle --}}
                <button @click="showFilters = !showFilters"
                        class="lg:hidden w-full btn-outline justify-center mb-4">
                    <i class="fa-solid fa-sliders"></i> {{ __('site.filters.show') }}
                </button>

                <form method="GET" action="{{ route('browse') }}"
                      class="card-luxury p-5 space-y-5 lg:sticky lg:top-32"
                      :class="{ 'hidden lg:block': ! showFilters }">

                    {{-- preserve type + sort across filter submits --}}
                    @if (! empty($filters['type']))
                        <input type="hidden" name="type" value="{{ $filters['type'] }}">
                    @endif
                    @if (! empty($filters['sort']))
                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                    @endif

                    <div class="flex items-center justify-between">
                        <h2 class="font-cairo font-bold text-lg flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-saudi-green"></i> {{ __('site.filters.title') }}
                        </h2>
                        @if ($activeChips->isNotEmpty())
                            <a href="{{ route('browse', array_filter(['type' => $filters['type'] ?? null])) }}"
                               class="text-xs text-red-500 hover:underline">{{ __('site.filters.clear') }}</a>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('site.filters.category') }}</label>
                        <x-select
                            name="category"
                            :options="$categories->pluck('name', 'slug')->all()"
                            :value="$filters['category'] ?? ''"
                            :placeholder="__('site.filters.all')"
                            icon="fa-solid fa-folder" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('site.filters.os') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($osList as $v => $l)
                                <label class="flex items-center gap-2 text-sm cursor-pointer rounded-lg border border-gray-100 px-2 py-1.5 hover:border-saudi-green/40 has-[:checked]:border-saudi-green has-[:checked]:bg-saudi-green/5">
                                    <input type="radio" name="os" value="{{ $v }}" @checked(($filters['os'] ?? '') === $v) class="text-saudi-green focus:ring-saudi-green">
                                    <span>{{ $l }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">{{ __('site.filters.license') }}</label>
                        <x-select
                            name="license"
                            :options="collect($licenseList)->mapWithKeys(fn ($l) => [$l => __('content.license.'.$l)])->all()"
                            :value="$filters['license'] ?? ''"
                            :placeholder="__('site.filters.all')"
                            icon="fa-solid fa-certificate" />
                    </div>

                    <button class="btn-primary w-full justify-center">
                        <i class="fa-solid fa-filter"></i> {{ __('site.filters.apply') }}
                    </button>
                </form>
            </aside>

            {{-- ===== RESULTS ===== --}}
            <div class="lg:col-span-3">

                {{-- toolbar --}}
                <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
                    <p class="text-sm text-gray-500">
                        {{ __('site.filters.results', ['count' => number_format($software->total())]) }}
                    </p>

                    <form method="GET" action="{{ route('browse') }}" class="flex items-center gap-2">
                        @foreach (['type', 'category', 'os', 'license'] as $k)
                            @if (! empty($filters[$k]))
                                <input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">
                            @endif
                        @endforeach
                        <label class="text-sm text-gray-500 whitespace-nowrap">{{ __('site.filters.sort') }}</label>
                        <div class="w-48">
                            <x-select
                                name="sort"
                                :options="collect($sortList)->mapWithKeys(fn ($l, $v) => [$v => __('site.filters.'.$l)])->all()"
                                :value="$filters['sort'] ?? 'recent'"
                                icon="fa-solid fa-arrow-down-wide-short"
                                submit-on-change />
                        </div>
                    </form>
                </div>

                {{-- active filter chips --}}
                @if ($activeChips->isNotEmpty())
                    <div class="flex items-center gap-2 flex-wrap mb-5">
                        <span class="text-xs text-gray-400">{{ __('site.filters.active') }}:</span>
                        @foreach ($activeChips as $key => $val)
                            @php
                                $display = match ($key) {
                                    'type' => ContentType::tryFrom($val)?->label() ?? $val,
                                    'category' => $categories->firstWhere('slug', $val)?->name ?? $val,
                                    'license' => __('content.license.'.$val),
                                    default => $val,
                                };
                            @endphp
                            <a href="{{ $without($key) }}"
                               class="inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full bg-saudi-green/10 text-saudi-green hover:bg-saudi-green/20">
                                {{ $display }}
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- grid --}}
                @if ($software->isEmpty())
                    <div class="card-luxury p-16 text-center">
                        <i class="fa-solid fa-box-open text-5xl text-gray-300"></i>
                        <p class="mt-4 text-gray-400">{{ __('site.empty') }}</p>
                        <a href="{{ route('browse') }}" class="btn-outline mt-5">{{ __('site.filters.reset') }}</a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach ($software as $item)
                            <x-software-card :software="$item" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $software->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>.no-scrollbar::-webkit-scrollbar{display:none}.no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}</style>
@endpush
