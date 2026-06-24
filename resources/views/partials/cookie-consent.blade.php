{{-- Professional cookie-consent banner with Google Consent Mode integration.
     State is stored in the `fnoon_consent` cookie (+ localStorage mirror):
     'all' | 'essential' | 'essential,analytics' | 'essential,ads' | … --}}
<div x-data="fnoonCookies()" x-cloak>

    {{-- Banner --}}
    <div x-show="open && ! panel"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="fixed inset-x-0 bottom-0 z-[70] px-3 pb-3 sm:px-5 sm:pb-5">
        <div class="card-luxury mx-auto flex max-w-4xl flex-col gap-4 p-5 shadow-2xl sm:flex-row sm:items-center sm:p-6">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-saudi-green/10 text-2xl text-saudi-green">
                <i class="fa-solid fa-cookie-bite"></i>
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="font-cairo text-base font-black text-luxury-black">{{ __('site.cookies.title') }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    {{ __('site.cookies.body') }}
                    <a href="{{ url('/privacy') }}" class="font-semibold text-saudi-green hover:underline">{{ __('site.cookies.learn_more') }}</a>
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button type="button" @click="panel = true"
                        class="rounded-xl px-3 py-2 text-sm font-bold text-gray-500 transition hover:bg-gray-100">
                    {{ __('site.cookies.customize') }}
                </button>
                <button type="button" @click="rejectAll()"
                        class="rounded-xl border border-saudi-green/30 px-4 py-2 text-sm font-bold text-saudi-green transition hover:bg-saudi-green/5">
                    {{ __('site.cookies.reject') }}
                </button>
                <button type="button" @click="acceptAll()" class="btn-primary text-sm">
                    <i class="fa-solid fa-check"></i> {{ __('site.cookies.accept_all') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Preferences modal --}}
    <div x-show="panel" x-transition.opacity class="fixed inset-0 z-[71] flex items-end justify-center p-3 sm:items-center sm:p-4">
        <div @click="open ? panel = false : null" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="card-luxury relative w-full max-w-lg p-6 shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-6"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-saudi-green/10 text-xl text-saudi-green">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div>
                    <h3 class="font-cairo text-lg font-black text-luxury-black">{{ __('site.cookies.prefs_title') }}</h3>
                    <p class="text-xs text-gray-400">{{ __('site.cookies.prefs_hint') }}</p>
                </div>
            </div>

            <div class="space-y-3">
                {{-- Necessary (locked) --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-gray-700"><i class="fa-solid fa-shield-halved text-saudi-green"></i> {{ __('site.cookies.cat_necessary') }}</span>
                        <span class="rounded-full bg-saudi-green/10 px-2.5 py-1 text-[11px] font-bold text-saudi-green">{{ __('site.cookies.always_on') }}</span>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-gray-500">{{ __('site.cookies.cat_necessary_desc') }}</p>
                </div>

                {{-- Analytics --}}
                <div class="rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-gray-700"><i class="fa-solid fa-chart-line text-saudi-green"></i> {{ __('site.cookies.cat_analytics') }}</span>
                        <button type="button" @click="analytics = ! analytics" role="switch" :aria-checked="analytics"
                                class="relative h-6 w-11 shrink-0 rounded-full transition" :class="analytics ? 'bg-saudi-green' : 'bg-gray-300'">
                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="analytics ? 'start-[1.375rem]' : 'start-0.5'"></span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-gray-500">{{ __('site.cookies.cat_analytics_desc') }}</p>
                </div>

                {{-- Ads --}}
                <div class="rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-bold text-gray-700"><i class="fa-solid fa-bullhorn text-saudi-green"></i> {{ __('site.cookies.cat_ads') }}</span>
                        <button type="button" @click="ads = ! ads" role="switch" :aria-checked="ads"
                                class="relative h-6 w-11 shrink-0 rounded-full transition" :class="ads ? 'bg-saudi-green' : 'bg-gray-300'">
                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="ads ? 'start-[1.375rem]' : 'start-0.5'"></span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-gray-500">{{ __('site.cookies.cat_ads_desc') }}</p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-2">
                <button type="button" @click="rejectAll()" class="text-sm font-semibold text-gray-400 hover:text-gray-600">{{ __('site.cookies.reject') }}</button>
                <div class="flex gap-2">
                    <button type="button" @click="acceptAll()" class="rounded-xl border border-saudi-green/30 px-4 py-2 text-sm font-bold text-saudi-green transition hover:bg-saudi-green/5">{{ __('site.cookies.accept_all') }}</button>
                    <button type="button" @click="savePrefs()" class="btn-primary text-sm">{{ __('site.cookies.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function fnoonCookies() {
            return {
                open: false,
                panel: false,
                analytics: false,
                ads: false,
                init() {
                    const c = this.read();
                    if (! c) {
                        this.open = true; // first visit → ask
                    } else {
                        this.analytics = c === 'all' || c.includes('analytics');
                        this.ads = c === 'all' || c.includes('ads');
                    }
                    window.addEventListener('fnoon-open-cookies', () => { this.open = true; this.panel = true; });
                },
                read() {
                    const m = document.cookie.match(/(?:^|;\s*)fnoon_consent=([^;]+)/);
                    return m ? decodeURIComponent(m[1]) : null;
                },
                persist(val) {
                    document.cookie = 'fnoon_consent=' + encodeURIComponent(val) + '; path=/; max-age=15552000; samesite=lax; secure';
                    try { localStorage.setItem('fnoon_consent', val); } catch (e) {}
                },
                apply(analytics, ads) {
                    if (window.gtag) {
                        gtag('consent', 'update', {
                            ad_storage: ads ? 'granted' : 'denied',
                            ad_user_data: ads ? 'granted' : 'denied',
                            ad_personalization: ads ? 'granted' : 'denied',
                            analytics_storage: analytics ? 'granted' : 'denied',
                        });
                    }
                },
                close() { this.open = false; this.panel = false; },
                acceptAll() {
                    this.analytics = true; this.ads = true;
                    this.persist('all'); this.apply(true, true); this.close();
                },
                rejectAll() {
                    this.analytics = false; this.ads = false;
                    this.persist('essential'); this.apply(false, false); this.close();
                },
                savePrefs() {
                    const parts = ['essential'];
                    if (this.analytics) parts.push('analytics');
                    if (this.ads) parts.push('ads');
                    this.persist(parts.join(',')); this.apply(this.analytics, this.ads); this.close();
                },
            };
        }
    </script>
@endpush
