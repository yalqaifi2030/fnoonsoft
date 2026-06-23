{{-- expects $video (LearningVideo); must live inside an x-data exposing play(type, src) + a video-modal --}}
@php
    $levelColor = ['beginner' => 'bg-green-100 text-green-700', 'intermediate' => 'bg-amber-100 text-amber-700', 'advanced' => 'bg-red-100 text-red-700'][$video->level] ?? 'bg-gray-100 text-gray-600';
    // A direct media URL (uploaded file or external direct link) — drives the auto
    // poster-frame + hover-to-preview. YouTube keeps its own thumbnail.
    $directSrc = $video->isYoutube() ? null : $video->videoSrc();
    $ytThumb = $video->thumbnailUrl();
@endphp
<div role="button" tabindex="0"
     x-data="videoCard()"
     @click="play('{{ $video->playerType() }}', @js($video->playerSrc()))"
     @keydown.enter.prevent="play('{{ $video->playerType() }}', @js($video->playerSrc()))"
     @keydown.space.prevent="play('{{ $video->playerType() }}', @js($video->playerSrc()))"
     @mouseenter="enter()" @mouseleave="leave()"
     class="card-luxury overflow-hidden text-start group cursor-pointer focus:outline-none focus:ring-2 focus:ring-saudi-green/40">
    <div class="relative aspect-video overflow-hidden bg-gradient-to-br from-luxury-black to-saudi-green-dark">
        @if ($directSrc)
            {{-- The video itself is the thumbnail: a frame is seeked on metadata load,
                 and it plays muted on hover for a live preview. --}}
            <video x-ref="prev" muted loop playsinline preload="metadata"
                   @if ($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif
                   @loadedmetadata="onMeta($el)"
                   src="{{ $directSrc }}#t=2"
                   class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:opacity-100 group-hover:scale-[1.03]"></video>
        @elseif ($ytThumb)
            <img src="{{ $ytThumb }}" alt="{{ $video->title }}" loading="lazy"
                 class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:opacity-100 group-hover:scale-105">
        @else
            <span class="absolute inset-0 flex items-center justify-center text-5xl text-white/15">
                <i class="fa-solid fa-{{ $video->isUpload() ? 'file-video' : 'film' }}"></i>
            </span>
        @endif

        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent"></span>

        {{-- Play overlay — fades away while previewing an uploaded video --}}
        <span class="absolute inset-0 flex items-center justify-center transition duration-300"
              @if ($directSrc) :class="previewing ? 'opacity-0' : 'opacity-100'" @endif>
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/90 text-xl text-saudi-green shadow-lg transition group-hover:scale-110">
                <i class="fa-solid fa-play"></i>
            </span>
        </span>

        {{-- Live-preview badge --}}
        @if ($directSrc)
            <span class="absolute top-2 start-2 inline-flex items-center gap-1 rounded-full bg-saudi-green/90 px-2 py-0.5 text-[10px] font-bold text-white shadow"
                  x-show="previewing" x-cloak x-transition.opacity>
                <i class="fa-solid fa-circle-play"></i> {{ __('learn.preview') }}
            </span>
        @endif

        @if ($video->duration)
            <span class="absolute bottom-2 end-2 rounded bg-black/75 px-1.5 py-0.5 text-[11px] text-white" dir="ltr">{{ $video->duration }}</span>
        @endif
    </div>
    <div class="p-4">
        <h3 class="line-clamp-2 text-sm font-bold group-hover:text-saudi-green">{{ $video->title }}</h3>
        <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $levelColor }}">
            {{ __('learn_admin.level.'.$video->level) }}
        </span>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function videoCard() {
                return {
                    previewing: false,
                    posterT: 2,
                    // Seek to a representative frame so the still isn't a black intro.
                    onMeta(v) {
                        try {
                            this.posterT = Math.min(5, (v.duration || 8) * 0.25);
                            v.currentTime = this.posterT;
                        } catch (e) {}
                    },
                    enter() {
                        const v = this.$refs.prev;
                        if (!v) return;
                        this.previewing = true;
                        const p = v.play();
                        if (p && p.catch) p.catch(() => { this.previewing = false; });
                    },
                    leave() {
                        const v = this.$refs.prev;
                        if (!v) return;
                        this.previewing = false;
                        try { v.pause(); v.currentTime = this.posterT; } catch (e) {}
                    },
                };
            }
        </script>
    @endpush
@endonce
