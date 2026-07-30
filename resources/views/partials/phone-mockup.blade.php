{{-- Realistic switchable phone mockup wrapping the live Flutter web preview.
     Shares the parent Alpine scope (needs `run` + `device`). Vars: $software.
     All device styling is done via CSS classes (.pm-phone.ios / .android) so
     Alpine's :style can never wipe structural rules like aspect-ratio. --}}
<div class="flex flex-col items-center">

    {{-- Device switcher --}}
    <div class="mb-5 inline-flex rounded-full bg-black/25 p-1 ring-1 ring-white/15">
        <button type="button" @click="device='ios'"
                :class="device==='ios' ? 'bg-white text-luxury-black shadow' : 'text-white/70 hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-bold transition">
            <i class="fa-brands fa-apple"></i> iPhone
        </button>
        <button type="button" @click="device='android'"
                :class="device==='android' ? 'bg-white text-luxury-black shadow' : 'text-white/70 hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-bold transition">
            <i class="fa-brands fa-android"></i> Android
        </button>
    </div>

    {{-- Phone --}}
    <div class="pm-phone" :class="device">
        {{-- side buttons --}}
        <span class="pm-btn ios-only b-silent"></span>
        <span class="pm-btn ios-only b-volup"></span>
        <span class="pm-btn ios-only b-voldn"></span>
        <span class="pm-btn ios-only b-power-ios"></span>
        <span class="pm-btn and-only b-power-and"></span>
        <span class="pm-btn and-only b-vol-and"></span>

        <div class="pm-frame">
            <div class="pm-screen">

                {{-- live app (loads on play) --}}
                <template x-if="run">
                    <iframe id="pmAppFrame" class="pm-iframe" src="{{ $software->livePreviewSrc() }}"
                            data-base="{{ $software->livePreviewSrc() }}" loading="lazy" scrolling="no"
                            title="{{ $software->name }}"
                            allow="fullscreen; clipboard-write; accelerometer; gyroscope"></iframe>
                </template>

                {{-- power-on / play screen --}}
                <button type="button" x-show="!run" @click="run = true" class="pm-play">
                    @if ($software->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                             alt="{{ $software->name }}" class="pm-play-icon">
                    @endif
                    <span class="pm-play-btn"><i class="fa-solid fa-play"></i></span>
                    <span class="font-cairo text-sm font-bold">{{ __('site.live_preview.play') }}</span>
                </button>

                {{-- status bar --}}
                <div class="pm-status">
                    <span class="pm-time" dir="ltr" x-text="device==='ios' ? '9:41' : '12:30'"></span>
                    <span class="pm-status-icons"><i class="fa-solid fa-signal"></i><i class="fa-solid fa-wifi"></i><i class="fa-solid fa-battery-three-quarters"></i></span>
                </div>

                <div class="pm-island ios-only"></div>
                <div class="pm-punch and-only"></div>
                <div class="pm-home ios-only"></div>
                <div class="pm-nav and-only">
                    <button type="button" class="pm-nav-btn" @click="window.pmNav('back')" aria-label="Back"><i class="fa-solid fa-caret-left"></i></button>
                    <button type="button" class="pm-nav-btn" @click="window.pmNav('home')" aria-label="Home"><i class="fa-regular fa-circle"></i></button>
                    <button type="button" class="pm-nav-btn" @click="window.pmNav('recent')" aria-label="Recent"><i class="fa-regular fa-square"></i></button>
                </div>

                <div class="pm-sheen"></div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .pm-phone { position:relative; width:400px; max-width:92vw; filter:drop-shadow(0 34px 55px rgba(0,0,0,.5)); }

            .pm-frame { padding:13px; border-radius:3.4rem;
                background:linear-gradient(145deg,#e2e4e9,#8b8e96 40%,#5a5c63 60%,#c7c9cf);
                box-shadow:inset 0 0 0 1px rgba(255,255,255,.4), inset 0 0 6px rgba(0,0,0,.5); }
            .pm-phone.android .pm-frame { padding:11px; border-radius:2.5rem;
                background:linear-gradient(145deg,#42444a,#161719 55%,#303237); }

            /* ~7-inch feel: a taller, larger screen (5:9 gives a big-tablet look) */
            .pm-screen { position:relative; aspect-ratio:5 / 9; overflow:hidden; background:#000; border-radius:2.5rem; }
            .pm-phone.android .pm-screen { border-radius:1.7rem; }

            .pm-iframe { position:absolute; left:0; width:100%; border:0; background:#fff; top:38px; height:calc(100% - 38px); }
            .pm-phone.android .pm-iframe { top:32px; height:calc(100% - 82px); }

            .pm-play { position:absolute; inset:0; z-index:10; display:flex; flex-direction:column; align-items:center;
                justify-content:center; gap:1rem; color:#fff; border:0; cursor:pointer;
                background:radial-gradient(120% 90% at 50% 0%, #0a7a3d 0%, #05351c 60%, #021109 100%); }
            .pm-play-icon { height:4rem; width:4rem; border-radius:1rem; object-fit:contain; box-shadow:0 0 0 1px rgba(255,255,255,.2); }
            .pm-play-btn { display:flex; height:3.5rem; width:3.5rem; align-items:center; justify-content:center;
                border-radius:9999px; background:rgba(255,255,255,.15); box-shadow:inset 0 0 0 1px rgba(255,255,255,.25);
                font-size:1.25rem; padding-inline-start:.2rem; transition:transform .2s; }
            .pm-play:hover .pm-play-btn { transform:scale(1.1); }

            .pm-status { position:absolute; inset-inline:0; top:0; z-index:30; height:38px; padding:0 24px;
                display:flex; align-items:center; justify-content:space-between; color:#fff; }
            .pm-phone.android .pm-status { height:32px; padding:0 18px; }
            .pm-time { font-size:14px; font-weight:700; }
            .pm-status-icons { display:flex; align-items:center; gap:7px; font-size:13px; }

            .pm-island { position:absolute; top:12px; left:50%; transform:translateX(-50%); z-index:40;
                width:120px; height:30px; border-radius:9999px; background:#000; }
            .pm-punch { position:absolute; top:12px; left:50%; transform:translateX(-50%); z-index:40;
                width:13px; height:13px; border-radius:9999px; background:#000; box-shadow:0 0 0 2px #111; }
            .pm-home { position:absolute; bottom:9px; left:50%; transform:translateX(-50%); z-index:40;
                width:140px; height:5px; border-radius:9999px; background:rgba(255,255,255,.75); pointer-events:none; }

            /* Bottom navigation bar (Android) — clearly present */
            .pm-nav { position:absolute; inset-inline:0; bottom:0; z-index:40; height:50px;
                display:flex; align-items:center; justify-content:center; gap:3.5rem;
                background:#0b0b0d; color:rgba(255,255,255,.9); border-top:1px solid rgba(255,255,255,.08); }
            .pm-nav-btn { background:transparent; border:0; color:inherit; cursor:pointer; padding:.35rem .55rem;
                border-radius:.55rem; display:inline-flex; align-items:center; justify-content:center; transition:background .15s, transform .1s; }
            .pm-nav-btn:hover { background:rgba(255,255,255,.1); }
            .pm-nav-btn:active { background:rgba(255,255,255,.2); transform:scale(.9); }
            .pm-nav i { font-size:1.15rem; }
            .pm-nav .fa-caret-left { font-size:1.5rem; }

            .pm-sheen { position:absolute; inset:0; z-index:50; pointer-events:none;
                background:linear-gradient(135deg, rgba(255,255,255,.16), transparent 38%); }

            /* device-specific element visibility */
            .pm-phone.ios .and-only { display:none !important; }
            .pm-phone.android .ios-only { display:none !important; }

            /* physical side buttons — percentage-based so they scale with the phone */
            .pm-btn { position:absolute; z-index:1; border-radius:3px; background:linear-gradient(90deg,#6b6d74,#3a3c42); }
            .b-silent   { inset-inline-start:-3px; top:13%; width:3px; height:4%; }
            .b-volup    { inset-inline-start:-3px; top:19%; width:3px; height:7%; }
            .b-voldn    { inset-inline-start:-3px; top:27%; width:3px; height:7%; }
            .b-power-ios{ inset-inline-end:-3px;   top:22%; width:3px; height:10%; }
            .b-power-and{ inset-inline-end:-3px;   top:22%; width:3px; height:8%; }
            .b-vol-and  { inset-inline-end:-3px;   top:32%; width:3px; height:11%; }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Bottom navigation bar → drive the live app inside the (same-origin) iframe.
            window.pmNav = function (action) {
                var f = document.getElementById('pmAppFrame');
                if (!f) { return; }           // not launched yet
                var base = f.getAttribute('data-base');
                try {
                    if (action === 'back') { f.contentWindow.history.back(); }
                    else if (action === 'home') { if (base) { f.src = base; } }
                    else if (action === 'recent') { f.contentWindow.location.reload(); }
                } catch (e) {
                    // cross-origin fallback: restart the app
                    if (base) { try { f.src = base; } catch (_) {} }
                }
            };
        </script>
    @endpush
@endonce
