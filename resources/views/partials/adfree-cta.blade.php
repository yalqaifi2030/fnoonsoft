{{-- Eye-catching, bilingual "sign in → no ads" prompt. Guests only, and only when
     ads are actually served. Dismissible (localStorage). --}}
@guest
    @if (app(\App\Support\Ads::class)->enabled())
        <div x-data="{ show: true, init() { try { this.show = ! localStorage.getItem('fnoon_adfree_x'); } catch (e) {} }, hide() { this.show = false; try { localStorage.setItem('fnoon_adfree_x', '1'); } catch (e) {} } }"
             x-show="show" x-cloak class="mx-auto w-full max-w-4xl px-4 my-8">
            <div class="relative overflow-hidden rounded-3xl p-8 text-center text-white sm:p-10"
                 style="background:linear-gradient(120deg,#006C35 0%,#00532a 55%,#0f1419 100%); box-shadow:0 25px 60px -20px rgba(0,108,53,.6);">

                {{-- decorative glow --}}
                <div class="absolute -top-16 -end-12 h-56 w-56 rounded-full" style="background:radial-gradient(circle,rgba(201,169,97,.28),transparent 70%);"></div>
                <div class="absolute -bottom-20 -start-12 h-56 w-56 rounded-full" style="background:radial-gradient(circle,rgba(255,255,255,.08),transparent 70%);"></div>

                <button type="button" @click="hide()" aria-label="close"
                        class="absolute end-4 top-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white/70 transition hover:bg-white/20 hover:text-white"><i class="fa-solid fa-xmark"></i></button>

                {{-- big pulsing "no ads" badge --}}
                <span class="relative mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-royal-gold/15 ring-2 ring-royal-gold/40"
                      style="animation:adfreePulse 2s ease-in-out infinite;">
                    <i class="fa-solid fa-ban text-4xl text-royal-gold"></i>
                </span>

                {{-- bilingual headline --}}
                <h2 class="relative font-cairo text-2xl font-black leading-tight sm:text-3xl">تصفّح الموقع <span class="text-royal-gold">بلا إعلانات</span> نهائيًّا!</h2>
                <p class="relative mt-1 font-cairo text-xl font-black tracking-tight text-white/95 sm:text-2xl" dir="ltr">Browse the site <span class="text-royal-gold">100% AD-FREE</span></p>

                {{-- bilingual subtitle --}}
                <p class="relative mx-auto mt-3 max-w-2xl text-base text-white/85">الإعلانات تظهر للزوّار فقط — سجّل دخولك <strong class="text-white">مجانًا</strong> وتختفي كل الإعلانات فورًا.</p>
                <p class="relative mx-auto max-w-2xl text-sm text-white/70" dir="ltr">Ads show for guests only — sign in <strong class="text-white">free</strong> and every ad disappears instantly.</p>

                {{-- buttons --}}
                <div class="relative mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="/dashboard/register"
                       class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3 text-base font-black text-saudi-green shadow-lg transition hover:-translate-y-0.5 hover:bg-royal-gold hover:text-luxury-black">
                        <i class="fa-solid fa-user-plus"></i> سجّل مجانًا وأزل الإعلانات · Sign up free
                    </a>
                    <a href="/dashboard/login"
                       class="inline-flex items-center gap-2 rounded-full bg-white/15 px-6 py-3 text-base font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                        <i class="fa-solid fa-right-to-bracket"></i> دخول · Sign in
                    </a>
                </div>
            </div>
        </div>

        @once
            @push('styles')
                <style>@keyframes adfreePulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(201,169,97,.35)}50%{transform:scale(1.06);box-shadow:0 0 0 14px rgba(201,169,97,0)}}</style>
            @endpush
        @endonce
    @endif
@endguest
