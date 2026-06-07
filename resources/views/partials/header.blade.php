@php($locale = app()->getLocale())
<header x-data="{ open: false }" class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-royal-gold/20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16 gap-4">
            {{-- Logo --}}
            @php($siteLogo = \App\Support\SiteBranding::logo())
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if ($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ \App\Models\Setting::text('site_name', __('site.name')) }}"
                         class="h-9 w-auto max-h-9 object-contain">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-saudi-green text-white">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </span>
                    <span class="font-cairo font-black text-xl text-luxury-black">{{ \App\Models\Setting::text('site_name', __('site.name')) }}</span>
                @endif
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="hover:text-saudi-green">{{ __('site.nav.home') }}</a>
                <a href="{{ route('browse', ['type' => 'application']) }}" class="hover:text-saudi-green">{{ __('site.nav.apps') }}</a>
                <a href="{{ route('browse', ['type' => 'script']) }}" class="hover:text-saudi-green">{{ __('site.nav.scripts') }}</a>
                <a href="{{ route('browse', ['type' => 'template']) }}" class="hover:text-saudi-green">{{ __('site.nav.templates') }}</a>
                <a href="{{ route('browse', ['type' => 'plugin']) }}" class="hover:text-saudi-green">{{ __('site.nav.plugins') }}</a>
                <a href="{{ route('learn') }}" class="inline-flex items-center gap-1 font-bold text-saudi-green hover:text-saudi-green-dark">
                    <i class="fa-solid fa-graduation-cap"></i> {{ __('learn.nav') }}
                </a>
                <a href="{{ route('blog.index') }}" class="hover:text-saudi-green">{{ __('site.nav.blog') }}</a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                {{-- Language switch --}}
                <a href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}"
                   class="hidden sm:inline-flex text-sm px-2 py-1 rounded-lg border border-royal-gold/30 hover:bg-royal-gold/10">
                    {{ $locale === 'ar' ? 'EN' : 'ع' }}
                </a>
                <a href="/upload" class="hidden sm:inline-flex btn-outline text-sm">
                    <i class="fa-solid fa-cloud-arrow-up"></i> {{ __('site.nav.upload') }}
                </a>
                <button @click="open = !open" class="md:hidden h-10 w-10 inline-flex items-center justify-center rounded-lg border border-royal-gold/30">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile nav --}}
    <div x-show="open" x-cloak class="md:hidden border-t border-royal-gold/20 bg-white">
        <nav class="px-4 py-3 flex flex-col gap-2 text-sm">
            <a href="{{ route('browse', ['type' => 'application']) }}">{{ __('site.nav.apps') }}</a>
            <a href="{{ route('browse', ['type' => 'script']) }}">{{ __('site.nav.scripts') }}</a>
            <a href="{{ route('browse', ['type' => 'template']) }}">{{ __('site.nav.templates') }}</a>
            <a href="{{ route('browse', ['type' => 'plugin']) }}">{{ __('site.nav.plugins') }}</a>
            <a href="{{ route('blog.index') }}">{{ __('site.nav.blog') }}</a>
            <a href="/upload">{{ __('site.nav.upload') }}</a>
            <a href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}">{{ $locale === 'ar' ? 'English' : 'العربية' }}</a>
        </nav>
    </div>
</header>
<style>[x-cloak]{display:none!important}</style>
