{{-- A single horizontal software row (icon · name/desc/category · platform+downloads · reputation · size).
     Vars: $software, optional $ranked (bool) + $rank (number). --}}
@php
    $osIcons = [
        'windows' => 'fa-brands fa-windows', 'macos' => 'fa-brands fa-apple', 'linux' => 'fa-brands fa-linux',
        'android' => 'fa-brands fa-android', 'ios' => 'fa-brands fa-apple', 'web' => 'fa-solid fa-globe',
    ];
    $size = (int) ($software->total_size_bytes ?? 0);
    $isRanked = ($ranked ?? false) && ! empty($rank);
@endphp
<a href="{{ route('software.show', $software) }}"
   class="card-luxury group flex items-center gap-3 p-3.5 transition hover:border-saudi-green/40 sm:gap-4">

    @if ($isRanked)
        <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-saudi-green/10 font-cairo text-base font-black text-saudi-green sm:flex" dir="ltr">{{ $rank }}</span>
    @endif

    {{-- icon --}}
    <div class="shrink-0">
        @if ($software->icon)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                 alt="{{ $software->name }}" width="56" height="56" loading="lazy" decoding="async"
                 class="h-14 w-14 rounded-xl bg-white object-contain ring-1 ring-royal-gold/15">
        @else
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-saudi-green/10 to-royal-gold/10 text-xl text-saudi-green"><i class="{{ $software->content_type->icon() }}"></i></span>
        @endif
    </div>

    {{-- name + description + category --}}
    <div class="min-w-0 flex-1">
        <h3 class="truncate font-cairo text-base font-bold text-luxury-black group-hover:text-saudi-green">
            {{ $software->name }}
            @if ($software->current_version)<span class="ms-1 text-xs font-normal text-gray-400" dir="ltr">v{{ $software->current_version }}</span>@endif
        </h3>
        <p class="truncate text-xs text-gray-500">{{ $software->short_description }}</p>
        @if ($software->category)
            <span class="text-xs font-semibold text-saudi-green">{{ $software->category->name }}</span>
        @endif
    </div>

    {{-- platform + downloads --}}
    <div class="hidden w-28 shrink-0 flex-col items-center gap-1 border-s border-gray-100 px-3 text-center md:flex">
        <span class="flex items-center gap-1.5 text-gray-600" dir="ltr">
            @forelse (array_slice((array) $software->os_support, 0, 3) as $os)
                <i class="{{ $osIcons[$os] ?? 'fa-solid fa-desktop' }}"></i>
            @empty
                <i class="fa-solid fa-desktop text-gray-300"></i>
            @endforelse
        </span>
        <span class="text-xs text-gray-400" dir="ltr"><i class="fa-solid fa-download text-saudi-green/70"></i> {{ number_format($software->downloads_count) }}</span>
    </div>

    {{-- reputation --}}
    <div class="hidden w-28 shrink-0 flex-col items-center gap-0.5 border-s border-gray-100 px-3 text-center lg:flex">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('site.reviews.title') }}</span>
        <span class="flex text-sm text-royal-gold" dir="ltr">
            @for ($s = 1; $s <= 5; $s++)<i class="fa-{{ $s <= round($software->rating_avg) ? 'solid' : 'regular' }} fa-star"></i>@endfor
        </span>
    </div>

    {{-- size --}}
    <div class="w-16 shrink-0 text-center sm:w-20">
        <span class="font-cairo text-base font-black text-luxury-black sm:text-lg" dir="ltr">{{ $size ? \Illuminate\Support\Number::fileSize($size, precision: 1) : '—' }}</span>
    </div>
</a>
