@php($locale = app()->getLocale())
<header x-data="{ open: false }" class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-royal-gold/20">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16 md:h-20 gap-4">
            {{-- Logo --}}
            @php($siteLogo = \App\Support\SiteBranding::logo())
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                @if ($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ \App\Models\Setting::text('site_name', __('site.name')) }}"
                         class="h-12 w-auto max-h-12 object-contain md:h-16 md:max-h-16">
                @else
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-saudi-green text-white text-lg md:h-14 md:w-14">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </span>
                    <span class="font-cairo font-black text-xl text-luxury-black md:text-2xl">{{ \App\Models\Setting::text('site_name', __('site.name')) }}</span>
                @endif
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="hover:text-saudi-green">{{ __('site.nav.home') }}</a>
                <a href="{{ route('browse', ['type' => 'application']) }}" class="hover:text-saudi-green">{{ __('site.nav.apps') }}</a>
                <a href="{{ route('browse', ['type' => 'script']) }}" class="hover:text-saudi-green">{{ __('site.nav.scripts') }}</a>
                <a href="{{ route('browse', ['type' => 'template']) }}" class="hover:text-saudi-green">{{ __('site.nav.templates') }}</a>
                <a href="{{ route('browse', ['type' => 'mobile_app']) }}" class="hover:text-saudi-green">{{ __('site.nav.mobile_apps') }}</a>
                <a href="{{ route('browse', ['type' => 'plugin']) }}" class="hover:text-saudi-green">{{ __('site.nav.plugins') }}</a>
                <a href="{{ route('learn') }}" class="inline-flex items-center gap-1 font-bold text-saudi-green hover:text-saudi-green-dark">
                    <i class="fa-solid fa-graduation-cap"></i> {{ __('learn.nav') }}
                </a>
                <a href="{{ route('blog.index') }}" class="hover:text-saudi-green">{{ __('site.nav.blog') }}</a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                {{-- Language switch (hidden while the site is locked to Arabic) --}}
                @unless (config('app.locale_locked'))
                    <a href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}"
                       class="hidden sm:inline-flex text-sm px-2 py-1 rounded-lg border border-royal-gold/30 hover:bg-royal-gold/10">
                        {{ $locale === 'ar' ? 'EN' : 'ع' }}
                    </a>
                @endunless
                <a href="{{ route('my.downloads') }}" title="{{ __('site.my_downloads.nav') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 rounded-lg border border-royal-gold/30 px-2.5 py-1.5 text-sm hover:bg-royal-gold/10">
                    <i class="fa-solid fa-clock-rotate-left text-saudi-green"></i>
                    <span class="hidden lg:inline">{{ __('site.my_downloads.nav') }}</span>
                </a>
                @if (\App\Models\Setting::get('member_uploads_enabled'))
                    @auth
                        <a href="/dashboard" class="hidden sm:inline-flex btn-primary text-sm">
                            <i class="fa-solid fa-folder-open"></i> {{ __('site.nav.my_files') }}
                        </a>
                    @else
                        <a href="/dashboard/login" class="hidden sm:inline-flex btn-outline text-sm">
                            <i class="fa-solid fa-right-to-bracket"></i> {{ __('site.nav.login') }}
                        </a>
                        <a href="/dashboard/register" class="hidden sm:inline-flex btn-primary text-sm">
                            <i class="fa-solid fa-user-plus"></i> {{ __('site.nav.register') }}
                        </a>
                    @endauth
                @endif
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
            <a href="{{ route('browse', ['type' => 'mobile_app']) }}">{{ __('site.nav.mobile_apps') }}</a>
            <a href="{{ route('browse', ['type' => 'plugin']) }}">{{ __('site.nav.plugins') }}</a>
            <a href="{{ route('blog.index') }}">{{ __('site.nav.blog') }}</a>
            <a href="{{ route('my.downloads') }}"><i class="fa-solid fa-clock-rotate-left text-saudi-green"></i> {{ __('site.my_downloads.nav') }}</a>
            @if (\App\Models\Setting::get('member_uploads_enabled'))
                @auth
                    <a href="/dashboard" class="font-bold text-saudi-green">{{ __('site.nav.my_files') }}</a>
                @else
                    <a href="/dashboard/login">{{ __('site.nav.login') }}</a>
                    <a href="/dashboard/register" class="font-bold text-saudi-green">{{ __('site.nav.register') }}</a>
                @endauth
            @endif
            @unless (config('app.locale_locked'))
                <a href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}">{{ $locale === 'ar' ? 'English' : 'العربية' }}</a>
            @endunless
        </nav>
    </div>
</header>
<style>[x-cloak]{display:none!important}</style>
