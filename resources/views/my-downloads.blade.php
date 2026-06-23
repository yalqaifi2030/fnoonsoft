@extends('layouts.app')

@section('title', __('site.my_downloads.title'))
@section('meta_description', __('site.my_downloads.subtitle'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10" x-data="fnoonDownloads()" x-cloak>

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-cairo text-2xl font-black text-luxury-black sm:text-3xl">
                <i class="fa-solid fa-clock-rotate-left text-saudi-green"></i> {{ __('site.my_downloads.title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('site.my_downloads.subtitle') }}</p>
        </div>
        <button type="button" x-show="items.length" @click="clear()"
                class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3.5 py-2 text-sm font-bold text-red-600 transition hover:bg-red-50">
            <i class="fa-solid fa-trash-can"></i> {{ __('site.my_downloads.clear') }}
        </button>
    </div>

    {{-- Banner: account vs device --}}
    @auth
        <a href="/dashboard/download-history"
           class="mb-6 flex items-center gap-3 rounded-2xl border border-saudi-green/20 bg-saudi-green/5 px-5 py-4 text-sm transition hover:bg-saudi-green/10">
            <i class="fa-solid fa-circle-check text-lg text-saudi-green"></i>
            <span class="flex-1 text-gray-600">{{ __('site.my_downloads.member_banner') }}</span>
            <span class="shrink-0 font-bold text-saudi-green">{{ __('site.my_downloads.member_link') }} <i class="fa-solid fa-arrow-left rtl:rotate-180"></i></span>
        </a>
    @else
        <a href="/dashboard/login"
           class="mb-6 flex items-center gap-3 rounded-2xl border border-royal-gold/30 bg-royal-gold/5 px-5 py-4 text-sm transition hover:bg-royal-gold/10">
            <i class="fa-solid fa-cloud-arrow-up text-lg text-bronze"></i>
            <span class="flex-1 text-gray-600">{{ __('site.my_downloads.guest_banner') }}</span>
            <span class="shrink-0 rounded-lg bg-saudi-green px-3 py-1.5 font-bold text-white">{{ __('site.my_downloads.guest_login') }}</span>
        </a>
    @endauth

    {{-- Empty state --}}
    <div x-show="items.length === 0"
         class="rounded-2xl border border-dashed border-gray-200 bg-white py-16 text-center">
        <i class="fa-solid fa-box-open mb-3 text-4xl text-gray-300"></i>
        <p class="font-bold text-gray-600">{{ __('site.my_downloads.empty') }}</p>
        <a href="{{ url('/browse') }}" class="btn-primary mt-4 inline-flex">
            <i class="fa-solid fa-compass"></i> {{ __('site.my_downloads.browse') }}
        </a>
    </div>

    {{-- List --}}
    <div x-show="items.length" class="overflow-hidden rounded-2xl border border-royal-gold/10 bg-white">
        <ul class="divide-y divide-gray-100">
            <template x-for="it in items" :key="it.slug">
                <li class="flex items-center gap-4 p-4 transition hover:bg-gray-50">
                    <template x-if="it.icon">
                        <img :src="it.icon" alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded-xl bg-white object-contain ring-1 ring-gray-100">
                    </template>
                    <template x-if="!it.icon">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-saudi-green text-white"><i class="fa-solid fa-cube"></i></span>
                    </template>

                    <div class="min-w-0 flex-1">
                        <a :href="url(it.slug)" class="block truncate font-bold text-gray-800 hover:text-saudi-green" x-text="it.name"></a>
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-gray-400">
                            <span x-show="it.dev" x-text="it.dev"></span>
                            <span><i class="fa-regular fa-clock"></i> <span x-text="ago(it.at)"></span></span>
                        </div>
                    </div>

                    <a :href="url(it.slug)" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-saudi-green px-3.5 py-2 text-sm font-bold text-white transition hover:bg-saudi-green-dark">
                        <i class="fa-solid fa-arrow-down"></i> <span class="hidden sm:inline">{{ __('site.my_downloads.open') }}</span>
                    </a>
                    <button type="button" @click="remove(it.slug)" :title="'{{ __('site.my_downloads.remove') }}'"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </li>
            </template>
        </ul>
    </div>
</div>

@push('scripts')
    <script>
        function fnoonDownloads() {
            return {
                items: [],
                init() {
                    try { this.items = JSON.parse(localStorage.getItem('fnoon_downloads') || '[]') || []; }
                    catch (e) { this.items = []; }
                },
                save() { try { localStorage.setItem('fnoon_downloads', JSON.stringify(this.items)); } catch (e) {} },
                remove(slug) { this.items = this.items.filter(x => x.slug !== slug); this.save(); },
                clear() { this.items = []; this.save(); },
                url(slug) { return '{{ url('/software') }}/' + encodeURIComponent(slug); },
                ago(ts) {
                    try {
                        const rtf = new Intl.RelativeTimeFormat(document.documentElement.lang || 'ar', { numeric: 'auto' });
                        const m = Math.round((Date.now() - ts) / 60000);
                        if (Math.abs(m) < 60) return rtf.format(-m, 'minute');
                        const h = Math.round(m / 60);
                        if (Math.abs(h) < 24) return rtf.format(-h, 'hour');
                        return rtf.format(-Math.round(h / 24), 'day');
                    } catch (e) { return new Date(ts).toLocaleDateString(); }
                },
            };
        }
    </script>
@endpush
@endsection
