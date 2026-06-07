{{-- expects $video (LearningVideo); must live inside an x-data with play(id) + a video-modal --}}
@php
    $levelColor = ['beginner' => 'bg-green-100 text-green-700', 'intermediate' => 'bg-amber-100 text-amber-700', 'advanced' => 'bg-red-100 text-red-700'][$video->level] ?? 'bg-gray-100 text-gray-600';
@endphp
<button type="button" @click="play('{{ $video->playerType() }}', @js($video->playerSrc()))" class="card-luxury overflow-hidden text-start group">
    <div class="relative aspect-video bg-gradient-to-br from-luxury-black to-saudi-green-dark">
        @if ($video->thumbnailUrl())
            <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" loading="lazy"
                 class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition">
        @else
            <span class="absolute inset-0 flex items-center justify-center text-white/15 text-5xl">
                <i class="fa-solid fa-{{ $video->isUpload() ? 'file-video' : 'film' }}"></i>
            </span>
        @endif
        <span class="absolute inset-0 flex items-center justify-center">
            <span class="h-14 w-14 rounded-full bg-white/90 text-saudi-green inline-flex items-center justify-center text-xl shadow-lg group-hover:scale-110 transition">
                <i class="fa-solid fa-play"></i>
            </span>
        </span>
        @if ($video->duration)
            <span class="absolute bottom-2 end-2 text-[11px] bg-black/75 text-white px-1.5 py-0.5 rounded" dir="ltr">{{ $video->duration }}</span>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-bold text-sm line-clamp-2 group-hover:text-saudi-green">{{ $video->title }}</h3>
        <span class="mt-2 inline-block text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $levelColor }}">
            {{ __('learn_admin.level.'.$video->level) }}
        </span>
    </div>
</button>
