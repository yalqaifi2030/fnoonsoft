@extends('layouts.app')

@section('title', __('assistant.title'))
@section('meta_description', __('assistant.subtitle'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10"
     x-data="aiAssistant(@js(route('assistant.recommend')))">

    {{-- header --}}
    <div class="text-center">
        <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-saudi-green to-royal-gold text-2xl text-white shadow-lg">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </span>
        <h1 class="mt-4 font-cairo text-3xl font-black text-luxury-black">{{ __('assistant.title') }}</h1>
        <p class="mx-auto mt-2 max-w-xl text-gray-500">{{ __('assistant.subtitle') }}</p>
    </div>

    {{-- input --}}
    <div class="card-luxury mt-7 p-4 sm:p-5">
        <textarea x-model="q" @keydown.enter.prevent="ask()" rows="2"
                  :disabled="loading"
                  placeholder="{{ __('assistant.placeholder') }}"
                  class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none"></textarea>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="button" @click="ask()" :disabled="loading || q.trim().length < 3"
                    class="btn-primary text-sm disabled:opacity-50">
                <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>
                <span x-text="loading ? @js(__('assistant.loading')) : @js(__('assistant.button'))"></span>
            </button>
            <span class="text-xs text-gray-400">{{ __('assistant.examples_label') }}</span>
        </div>

        {{-- example chips --}}
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach (__('assistant.examples') as $ex)
                <button type="button" @click="q = @js($ex); ask()" :disabled="loading"
                        class="rounded-full bg-saudi-green/8 px-3 py-1 text-xs font-medium text-saudi-green transition hover:bg-saudi-green/15">{{ $ex }}</button>
            @endforeach
        </div>
    </div>

    {{-- error --}}
    <div x-show="error" x-cloak class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
        <i class="fa-solid fa-circle-exclamation"></i> <span x-text="error"></span>
    </div>

    {{-- loading skeleton --}}
    <div x-show="loading" x-cloak class="mt-6 space-y-3">
        <template x-for="i in 3" :key="i">
            <div class="card-luxury flex items-center gap-3 p-4">
                <div class="h-12 w-12 shrink-0 animate-pulse rounded-xl bg-gray-100"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-1/3 animate-pulse rounded bg-gray-100"></div>
                    <div class="h-3 w-2/3 animate-pulse rounded bg-gray-100"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- results --}}
    <div x-show="!loading && answered" x-cloak class="mt-6">
        <p x-show="intro" class="mb-4 rounded-xl bg-saudi-green/5 px-4 py-3 text-sm font-medium text-saudi-green" x-text="intro"></p>

        <template x-if="results.length">
            <div class="space-y-3">
                <template x-for="(r, i) in results" :key="i">
                    <a :href="r.url" class="card-luxury group flex items-center gap-4 p-4 transition hover:border-saudi-green/40">
                        <template x-if="r.icon">
                            <img :src="r.icon" :alt="r.name" class="h-14 w-14 shrink-0 rounded-xl bg-white object-contain ring-1 ring-royal-gold/15">
                        </template>
                        <template x-if="!r.icon">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-saudi-green/10 text-saudi-green"><i class="fa-solid fa-cube text-xl"></i></span>
                        </template>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-cairo font-bold text-luxury-black group-hover:text-saudi-green" x-text="r.name"></h3>
                            <p x-show="r.category" class="text-xs font-semibold text-saudi-green/80" x-text="r.category"></p>
                            <p class="mt-1 text-sm leading-relaxed text-gray-600" x-text="r.reason"></p>
                        </div>
                        <span class="hidden shrink-0 items-center gap-1 self-center rounded-lg bg-saudi-green px-3 py-2 text-xs font-bold text-white sm:inline-flex">
                            {{ __('assistant.view_program') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-[10px]"></i>
                        </span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!results.length">
            <div class="card-luxury p-10 text-center">
                <i class="fa-solid fa-magnifying-glass text-4xl text-gray-300"></i>
                <p class="mt-3 text-gray-500">{{ __('assistant.no_results') }}</p>
                <a href="{{ route('browse') }}" class="btn-outline mt-4">{{ __('assistant.browse_all') }}</a>
            </div>
        </template>

        <p class="mt-4 text-center text-[11px] text-gray-400">{{ __('assistant.disclaimer') }}</p>
    </div>
</div>

@push('scripts')
    <script>
        function aiAssistant(endpoint) {
            return {
                endpoint: endpoint, q: '', loading: false, answered: false, error: '', intro: '', results: [],
                async ask() {
                    const q = this.q.trim();
                    if (q.length < 3 || this.loading) return;
                    this.loading = true; this.error = ''; this.answered = false;
                    try {
                        const res = await fetch(this.endpoint + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (!res.ok) { this.error = data.error || '{{ __('assistant.error') }}'; }
                        else { this.intro = data.intro || ''; this.results = data.results || []; this.answered = true; }
                    } catch (e) {
                        this.error = '{{ __('assistant.error') }}';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
@endsection
