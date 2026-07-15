{{-- Professional top banner encouraging guests to register for free storage.
     Shows only to guests, only when member uploads are enabled, dismissible per session. --}}
@guest
    @if (\App\Models\Setting::get('member_uploads_enabled'))
        @php($gb = (int) (\App\Models\Setting::get('member_quota_gb', 20) ?: 20))
        <div x-data="fnoonSignupPromo()" x-init="init()" x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="relative z-30 border-b border-white/10 bg-gradient-to-r from-saudi-green to-[#00532a] text-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1.5 px-10 py-2.5 text-center text-sm">
                <span class="inline-flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-cloud-arrow-up text-royal-gold"></i>
                    {!! __('site.signup_promo.title', ['gb' => '<strong class="font-black text-royal-gold">'.$gb.' GB</strong>']) !!}
                </span>
                <a href="/dashboard/register"
                   class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-1 text-xs font-black text-saudi-green shadow-sm transition hover:-translate-y-0.5 hover:bg-royal-gold hover:text-luxury-black">
                    <i class="fa-solid fa-user-plus"></i> {{ __('site.signup_promo.cta') }}
                </a>
            </div>
            <button type="button" @click="dismiss()" aria-label="close"
                    class="absolute end-3 top-1/2 -translate-y-1/2 text-white/70 transition hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @push('scripts')
            <script>
                function fnoonSignupPromo() {
                    return {
                        show: false,
                        init() {
                            try { this.show = ! sessionStorage.getItem('fnoon_signup_promo_x'); }
                            catch (e) { this.show = true; }
                        },
                        dismiss() {
                            this.show = false;
                            try { sessionStorage.setItem('fnoon_signup_promo_x', '1'); } catch (e) {}
                        },
                    };
                }
            </script>
        @endpush
    @endif
@endguest
