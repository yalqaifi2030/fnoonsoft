{{-- Realistic switchable phone mockup wrapping the live Flutter web preview.
     Shares the parent Alpine scope (needs `run` + `device`). Vars: $software. --}}
<div class="flex flex-col items-center">

    {{-- Device switcher --}}
    <div class="mb-5 inline-flex rounded-full bg-black/25 p-1 ring-1 ring-white/15" role="tablist">
        <button type="button" @click="device='ios'" role="tab"
                :class="device==='ios' ? 'bg-white text-luxury-black shadow' : 'text-white/70 hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-bold transition">
            <i class="fa-brands fa-apple"></i> iPhone
        </button>
        <button type="button" @click="device='android'" role="tab"
                :class="device==='android' ? 'bg-white text-luxury-black shadow' : 'text-white/70 hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-bold transition">
            <i class="fa-brands fa-android"></i> Android
        </button>
    </div>

    {{-- Phone --}}
    <div class="phone-wrap relative" style="width:300px;max-width:88vw;">
        {{-- side buttons (on the metal frame) --}}
        <span class="phone-btn" :class="device==='ios' ? 'ios-silent' : 'and-hide'"></span>
        <span class="phone-btn" :class="device==='ios' ? 'ios-volup' : 'and-hide'"></span>
        <span class="phone-btn" :class="device==='ios' ? 'ios-voldn' : 'and-hide'"></span>
        <span class="phone-btn" :class="device==='ios' ? 'ios-power' : 'and-power'"></span>
        <span class="phone-btn" :class="device==='android' ? 'and-vol' : 'and-hide'"></span>

        {{-- metal frame --}}
        <div class="phone-frame relative bg-black"
             :style="device==='ios'
                ? 'border-radius:3rem; padding:11px; background:linear-gradient(145deg,#d9dbe0,#8b8e96 40%,#5a5c63 60%,#c7c9cf);'
                : 'border-radius:2.1rem; padding:9px; background:linear-gradient(145deg,#3a3c42,#1a1b1f 55%,#2c2e33);'">

            {{-- screen --}}
            <div class="relative overflow-hidden bg-black"
                 :style="device==='ios' ? 'border-radius:2.15rem;' : 'border-radius:1.4rem;'"
                 style="aspect-ratio:9/19.5;">

                {{-- the live app (Flutter web / Appetize) — starts below the status bar --}}
                <template x-if="run">
                    <iframe src="{{ $software->livePreviewSrc() }}" loading="lazy" scrolling="no"
                            class="absolute inset-x-0 w-full bg-white"
                            :style="device==='ios' ? 'top:34px; bottom:0;' : 'top:28px; bottom:34px;'"
                            style="height:auto;" title="{{ $software->name }}"
                            allow="fullscreen; clipboard-write; accelerometer; gyroscope"></iframe>
                </template>

                {{-- power-on / play screen --}}
                <button type="button" x-show="!run" @click="run = true"
                        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 text-white"
                        style="background:radial-gradient(120% 90% at 50% 0%, #0a7a3d 0%, #05351c 60%, #021109 100%);">
                    @if ($software->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                             alt="{{ $software->name }}" class="h-16 w-16 rounded-2xl object-contain ring-1 ring-white/20">
                    @endif
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 text-xl ring-1 ring-white/25 transition hover:scale-110">
                        <i class="fa-solid fa-play ms-1"></i>
                    </span>
                    <span class="font-cairo text-sm font-bold">{{ __('site.live_preview.play') }}</span>
                </button>

                {{-- status bar (part of the phone chrome) --}}
                <div class="absolute inset-x-0 top-0 z-30 flex items-center justify-between text-white"
                     :style="device==='ios' ? 'height:34px; padding:0 20px;' : 'height:28px; padding:0 14px;'">
                    <span class="text-[12px] font-bold" dir="ltr" x-text="device==='ios' ? '9:41' : '12:30'"></span>
                    <span class="flex items-center gap-1.5 text-[11px]">
                        <i class="fa-solid fa-signal"></i>
                        <i class="fa-solid fa-wifi"></i>
                        <i class="fa-solid fa-battery-three-quarters"></i>
                    </span>
                </div>

                {{-- iOS Dynamic Island --}}
                <div x-show="device==='ios'"
                     class="absolute left-1/2 top-[9px] z-40 -translate-x-1/2 rounded-full bg-black"
                     style="height:26px; width:95px;"></div>

                {{-- Android hole-punch camera --}}
                <div x-show="device==='android'"
                     class="absolute left-1/2 top-[9px] z-40 -translate-x-1/2 rounded-full bg-black ring-2 ring-[#111]"
                     style="height:11px; width:11px;"></div>

                {{-- iOS home indicator --}}
                <div x-show="device==='ios'"
                     class="pointer-events-none absolute bottom-[7px] left-1/2 z-40 -translate-x-1/2 rounded-full bg-white/70"
                     style="height:4px; width:110px;"></div>

                {{-- Android nav bar --}}
                <div x-show="device==='android'"
                     class="absolute inset-x-0 bottom-0 z-30 flex items-center justify-center gap-11 bg-black text-white/85"
                     style="height:34px;">
                    <i class="fa-solid fa-caret-left text-lg"></i>
                    <i class="fa-regular fa-circle"></i>
                    <i class="fa-regular fa-square"></i>
                </div>

                {{-- glass sheen --}}
                <div class="pointer-events-none absolute inset-0 z-50"
                     style="background:linear-gradient(135deg, rgba(255,255,255,.16), transparent 38%);"></div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .phone-wrap { filter: drop-shadow(0 30px 45px rgba(0,0,0,.45)); }
            .phone-frame { box-shadow: inset 0 0 0 1px rgba(255,255,255,.35), inset 0 0 4px rgba(0,0,0,.5); }
            /* Physical side buttons on the metal frame */
            .phone-btn { position:absolute; z-index:1; border-radius:3px; background:linear-gradient(90deg,#6b6d74,#3a3c42); }
            .ios-silent { inset-inline-start:-3px; top:96px;  width:3px; height:26px; }
            .ios-volup  { inset-inline-start:-3px; top:132px; width:3px; height:44px; }
            .ios-voldn  { inset-inline-start:-3px; top:186px; width:3px; height:44px; }
            .ios-power  { inset-inline-end:-3px;  top:150px; width:3px; height:64px; }
            .and-power  { inset-inline-end:-3px;  top:150px; width:3px; height:52px; }
            .and-vol    { inset-inline-end:-3px;  top:210px; width:3px; height:70px; }
            .and-hide   { display:none; }
        </style>
    @endpush
@endonce
