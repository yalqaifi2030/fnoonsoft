{{-- Page-level player modal; needs x-data with { open, type, src, play(type, src) } --}}
<div x-show="open" x-cloak @keydown.escape.window="open = false"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     x-transition.opacity>
    <div @click="open = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-4xl">
        <button @click="open = false" class="absolute -top-10 end-0 text-white/80 hover:text-white text-2xl">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="aspect-video rounded-xl overflow-hidden shadow-2xl bg-black ring-1 ring-royal-gold/30">
            <template x-if="open && type==='youtube'">
                <iframe :src="'https://www.youtube-nocookie.com/embed/' + src + '?autoplay=1&rel=0'"
                        class="w-full h-full" title="video" loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </template>
            <template x-if="open && type==='video'">
                <video :src="src" class="w-full h-full" controls autoplay playsinline></video>
            </template>
        </div>
    </div>
</div>
