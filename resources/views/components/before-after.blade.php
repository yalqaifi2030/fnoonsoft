@props([
    'before' => null,
    'after' => null,
    'type' => 'image',     // image | video
    'beforeLabel' => null,
    'afterLabel' => null,
    'caption' => null,
])

@php
    $bl = $beforeLabel ?: __('site.before_after.before');
    $al = $afterLabel ?: __('site.before_after.after');
    $isVideo = $type === 'video';
@endphp

@if ($before && $after)
<figure class="m-0">
    {{-- Drag-to-reveal comparison. Kept internally LTR (before = left, after =
         right) so the clip-path math is identical in RTL and LTR layouts. --}}
    <div dir="ltr"
         x-data="{
            pos: 50, drag: false, playing: false,
            move(e) {
                const r = $el.getBoundingClientRect();
                const cx = e.touches ? e.touches[0].clientX : e.clientX;
                this.pos = Math.max(0, Math.min(100, (cx - r.left) / r.width * 100));
            },
            toggle() {
                const v = $el.querySelectorAll('video');
                if (! v.length) return;
                if (this.playing) { v.forEach(x => x.pause()); }
                else { v.forEach(x => x.play().catch(() => {})); }
                this.playing = ! this.playing;
            },
            sync() {
                const v = $el.querySelectorAll('video');
                if (v.length === 2 && Math.abs(v[0].currentTime - v[1].currentTime) > 0.15) {
                    v[1].currentTime = v[0].currentTime;
                }
            }
         }"
         @pointerdown.prevent="drag = true; move($event)"
         @pointermove.window="drag && move($event)"
         @pointerup.window="drag = false"
         @touchstart.prevent="drag = true; move($event)"
         @touchmove.window="drag && move($event)"
         @touchend.window="drag = false"
         class="ba relative w-full touch-none select-none overflow-hidden rounded-xl border border-royal-gold/15 bg-black/5">

        {{-- AFTER — base layer defines the height --}}
        @if ($isVideo)
            <video src="{{ $after }}" class="block w-full" muted loop playsinline preload="metadata"
                   @timeupdate="sync()"></video>
        @else
            <img src="{{ $after }}" alt="{{ $al }}" class="block w-full" loading="lazy">
        @endif

        {{-- BEFORE — clipped overlay revealed by the handle --}}
        <div class="absolute inset-0 overflow-hidden"
             :style="`clip-path: inset(0 ${100 - pos}% 0 0); -webkit-clip-path: inset(0 ${100 - pos}% 0 0)`">
            @if ($isVideo)
                <video src="{{ $before }}" class="absolute inset-0 h-full w-full object-cover"
                       muted loop playsinline preload="metadata"></video>
            @else
                <img src="{{ $before }}" alt="{{ $bl }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
            @endif
        </div>

        {{-- Labels --}}
        <span class="pointer-events-none absolute left-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-bold text-white">{{ $bl }}</span>
        <span class="pointer-events-none absolute right-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-bold text-white">{{ $al }}</span>

        @if ($isVideo)
            <button type="button" @click.stop="toggle()" @pointerdown.stop @touchstart.stop
                    class="absolute bottom-3 left-1/2 z-20 inline-flex h-11 w-11 -translate-x-1/2 items-center justify-center rounded-full bg-white/90 text-saudi-green shadow-lg backdrop-blur transition hover:bg-white">
                <i class="fa-solid" :class="playing ? 'fa-pause' : 'fa-play'"></i>
            </button>
        @endif

        {{-- Handle --}}
        <div class="absolute inset-y-0 z-10 w-0.5 cursor-ew-resize bg-white shadow-[0_0_0_1px_rgba(0,0,0,.25)]"
             :style="`left:${pos}%; transform: translateX(-50%)`">
            <span class="absolute left-1/2 top-1/2 inline-flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-saudi-green shadow-lg ring-1 ring-black/10">
                <i class="fa-solid fa-arrows-left-right text-sm"></i>
            </span>
        </div>
    </div>

    @if ($caption)
        <figcaption class="mt-2 text-center text-sm text-gray-500">{{ $caption }}</figcaption>
    @endif
</figure>
@endif
