{{-- Gentle, auto-dismissing toast that nudges visitors to rate the site.
     All text & timing come from admin settings (Settings → Rating nudge). --}}
@if (\App\Models\Setting::get('rating_nudge_enabled'))
    @php
        $loc = app()->getLocale();
        $t = \App\Models\Setting::get('rating_nudge_title');
        $m = \App\Models\Setting::get('rating_nudge_message');
        $c = \App\Models\Setting::get('rating_nudge_cta_label');
        $rn = [
            'title' => is_array($t) ? ($t[$loc] ?? $t['ar'] ?? '') : (string) $t,
            'message' => is_array($m) ? ($m[$loc] ?? $m['ar'] ?? '') : (string) $m,
            'ctaLabel' => is_array($c) ? ($c[$loc] ?? $c['ar'] ?? '') : '',
            'ctaUrl' => \App\Models\Setting::text('rating_nudge_cta_url', ''),
            'delay' => max(0, (int) (\App\Models\Setting::get('rating_nudge_delay') ?: 8)),
            'duration' => max(3, (int) (\App\Models\Setting::get('rating_nudge_duration') ?: 10)),
        ];
    @endphp

    @if ($rn['title'] || $rn['message'])
        <div x-data="fnoonRatingNudge(@js($rn))" x-init="init()"
             x-show="show" x-cloak style="display:none"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-8"
             class="fixed bottom-5 start-5 z-[60] w-[340px] max-w-[calc(100vw-2.5rem)]"
             @mouseenter="pause()" @mouseleave="resume()">
            <div class="relative overflow-hidden rounded-2xl border border-royal-gold/25 bg-white p-5 shadow-2xl">
                <div class="absolute -end-8 -top-8 h-24 w-24 rounded-full bg-royal-gold/10 blur-2xl"></div>
                <button type="button" @click="dismiss()" class="absolute end-2.5 top-2.5 text-gray-300 transition hover:text-gray-500" aria-label="close"><i class="fa-solid fa-xmark"></i></button>

                <div class="relative flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-royal-gold to-saudi-green text-lg text-white shadow"><i class="fa-solid fa-star"></i></span>
                    <div class="min-w-0">
                        <h4 class="font-cairo font-black text-luxury-black" x-text="data.title"></h4>
                        <p class="mt-0.5 text-sm leading-relaxed text-gray-500" x-text="data.message"></p>
                        <div class="mt-2 flex gap-0.5 text-sm text-royal-gold" dir="ltr">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <template x-if="data.ctaUrl && data.ctaLabel">
                            <a :href="data.ctaUrl" @click="dismiss()" class="btn-primary mt-3 text-xs"><i class="fa-solid fa-heart"></i> <span x-text="data.ctaLabel"></span></a>
                        </template>
                    </div>
                </div>

                {{-- countdown bar --}}
                <div class="absolute inset-x-0 bottom-0 h-1 bg-gray-100">
                    <div class="h-full bg-saudi-green/70" :style="`width:${barWidth}%; transition: width ${data.duration}s linear`"></div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                function fnoonRatingNudge(data) {
                    return {
                        data: data, show: false, barWidth: 100, _t: null,
                        init() {
                            try { if (sessionStorage.getItem('fnoon_rating_nudge_seen')) return; } catch (e) {}
                            setTimeout(() => this.reveal(), this.data.delay * 1000);
                        },
                        reveal() {
                            this.show = true;
                            this.$nextTick(() => requestAnimationFrame(() => { this.barWidth = 0; }));
                            this._t = setTimeout(() => this.dismiss(), this.data.duration * 1000);
                        },
                        pause() { if (this._t) { clearTimeout(this._t); this._t = null; } },
                        resume() {
                            if (!this.show || this._t) return;
                            this._t = setTimeout(() => this.dismiss(), 4000);
                        },
                        dismiss() {
                            this.show = false;
                            if (this._t) clearTimeout(this._t);
                            try { sessionStorage.setItem('fnoon_rating_nudge_seen', '1'); } catch (e) {}
                        },
                    };
                }
            </script>
        @endpush
    @endif
@endif
