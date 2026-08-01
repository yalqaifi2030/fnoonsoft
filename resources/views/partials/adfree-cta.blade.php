{{-- Compact bilingual "sign in → no ads" prompt. Guests only, and only when ads
     are actually served. Dismissible (localStorage). --}}
@guest
    @if (app(\App\Support\Ads::class)->enabled())
        <div x-data="{ show: true, init() { try { this.show = ! localStorage.getItem('fnoon_adfree_x'); } catch (e) {} }, hide() { this.show = false; try { localStorage.setItem('fnoon_adfree_x', '1'); } catch (e) {} } }"
             x-show="show" x-cloak class="mx-auto w-full max-w-7xl px-4 my-6">
            <div class="relative overflow-hidden rounded-2xl border border-royal-gold/30 p-5 text-center text-white"
                 style="background:linear-gradient(120deg,#006C35,#00532a);">
                <div class="absolute -top-10 -end-8 h-32 w-32 rounded-full" style="background:radial-gradient(circle,rgba(201,169,97,.25),transparent 70%);"></div>

                <button type="button" @click="hide()" aria-label="close"
                        class="absolute end-3 top-3 text-white/70 transition hover:text-white"><i class="fa-solid fa-xmark"></i></button>

                <p class="relative font-cairo text-lg font-black">
                    <i class="fa-solid fa-ban text-royal-gold"></i>
                    تصفّح <span class="text-royal-gold">بلا إعلانات</span> · <span dir="ltr">Browse <span class="text-royal-gold">Ad-Free</span></span>
                </p>
                <p class="relative mx-auto mt-1 max-w-xl text-sm text-white/85">سجّل دخولك مجانًا وتختفي كل الإعلانات · Sign in free and all ads disappear.</p>

                <div class="relative mt-3.5 flex flex-wrap items-center justify-center gap-2.5">
                    <a href="/dashboard/register"
                       class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-black text-saudi-green shadow transition hover:-translate-y-0.5 hover:bg-royal-gold hover:text-luxury-black">
                        <i class="fa-solid fa-user-plus"></i> سجّل مجانًا · Sign up free
                    </a>
                    <a href="/dashboard/login"
                       class="inline-flex items-center gap-2 rounded-full bg-white/15 px-5 py-2 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                        <i class="fa-solid fa-right-to-bracket"></i> دخول · Sign in
                    </a>
                </div>
            </div>
        </div>
    @endif
@endguest
