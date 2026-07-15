@extends('layouts.app')

@section('title', __('site.hero.search_button') . ': ' . $term)
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8" x-data="{ requested: false, requesting: false }">
    <form action="{{ route('search') }}" method="GET" class="mb-6 flex max-w-2xl gap-2">
        <input name="q" value="{{ $term }}" placeholder="{{ __('site.hero.search_placeholder') }}"
               class="flex-1 rounded-xl border-gray-200 px-5 py-3 focus:ring-2 focus:ring-royal-gold">
        <button class="btn-primary px-6"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    {{-- Trending (popular, fulfilled) searches --}}
    @isset($trending)
        @if ($trending->isNotEmpty())
            <div class="mb-8 flex flex-wrap items-center gap-2">
                <span class="text-sm font-bold text-gray-500"><i class="fa-solid fa-fire text-royal-gold"></i> {{ __('search.trending') }}</span>
                @foreach ($trending as $t)
                    <a href="{{ route('search', ['q' => $t]) }}"
                       class="rounded-full bg-saudi-green/8 px-3 py-1 text-xs font-medium text-saudi-green transition hover:bg-saudi-green/15">{{ $t }}</a>
                @endforeach
            </div>
        @endif
    @endisset

    @if ($results === null)
        <p class="text-gray-400">{{ __('site.hero.search_placeholder') }}</p>

    @elseif ($results->isEmpty())
        {{-- Zero results → invite the visitor to request the program --}}
        <div class="card-luxury mx-auto max-w-xl p-10 text-center">
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-saudi-green/10 text-2xl text-saudi-green"><i class="fa-solid fa-box-open"></i></span>
            <h2 class="mt-4 font-cairo text-xl font-black text-luxury-black">{{ __('search.none_title', ['term' => $term]) }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">{{ __('search.none_hint') }}</p>

            <div class="mt-6">
                <button type="button" x-show="!requested" :disabled="requesting"
                        @click="requesting = true; fetch(@js(route('search.request')) + '?q=' + encodeURIComponent(@js($term)), { headers: { 'Accept': 'application/json' } }).then(() => { requested = true }).finally(() => requesting = false)"
                        class="btn-primary disabled:opacity-60">
                    <i class="fa-solid" :class="requesting ? 'fa-spinner fa-spin' : 'fa-hand-point-up'"></i>
                    {{ __('search.request_cta') }}
                </button>
                <div x-show="requested" x-cloak class="inline-flex items-center gap-2 rounded-xl bg-green-50 px-5 py-3 font-bold text-green-700">
                    <i class="fa-solid fa-circle-check"></i> {{ __('search.request_done') }}
                </div>
            </div>

            <a href="{{ route('browse') }}" class="mt-5 inline-block text-sm font-semibold text-gray-400 hover:text-saudi-green">{{ __('search.browse_all') }}</a>
        </div>

    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($results as $item)
                <x-software-card :software="$item" />
            @endforeach
        </div>
        <div class="mt-8">{{ $results->links() }}</div>
    @endif
</div>
@endsection
