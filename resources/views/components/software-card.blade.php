@props(['software'])

@php
    $licenseLabel = __('content.license.'.$software->license_type);
    $isFree = in_array($software->license_type, ['free', 'open_source']);
@endphp

<a href="{{ route('software.show', $software) }}"
   class="card-luxury p-4 flex flex-col gap-3 group">

    <div class="flex items-start gap-3">
        @if ($software->icon)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                 alt="{{ $software->name }}" width="56" height="56"
                 class="h-14 w-14 rounded-xl object-cover shrink-0 ring-1 ring-royal-gold/15">
        @else
            <span class="h-14 w-14 rounded-xl bg-gradient-to-br from-saudi-green/10 to-royal-gold/10 text-saudi-green inline-flex items-center justify-center shrink-0">
                <i class="{{ $software->content_type->icon() }} text-xl"></i>
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <span class="inline-flex items-center gap-1 max-w-full text-[10px] px-2 py-0.5 rounded-full bg-saudi-green/8 text-saudi-green/90 font-semibold mb-1">
                <i class="{{ $software->content_type->icon() }}"></i>
                <span class="truncate">{{ $software->content_type->label() }}</span>
            </span>
            <h3 class="font-cairo font-bold text-luxury-black truncate group-hover:text-saudi-green transition">{{ $software->name }}</h3>
            <p class="text-xs text-gray-500 truncate">{{ $software->developer?->name }}</p>
            <div class="flex items-center gap-1 mt-1 text-royal-gold text-xs" dir="ltr">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="fa-{{ $i <= round($software->rating_avg) ? 'solid' : 'regular' }} fa-star"></i>
                @endfor
                <span class="text-gray-400 ms-1">{{ $software->rating_avg }}</span>
            </div>
        </div>
    </div>

    <p class="text-sm text-gray-600 line-clamp-2 flex-1">{{ $software->short_description }}</p>

    {{-- OS chips --}}
    @php
        $osIcons = [
            'windows' => 'fa-brands fa-windows',
            'macos'   => 'fa-brands fa-apple',
            'linux'   => 'fa-brands fa-linux',
            'android' => 'fa-brands fa-android',
            'ios'     => 'fa-brands fa-apple',
            'web'     => 'fa-solid fa-globe',
        ];
    @endphp
    @if (is_array($software->os_support) && count($software->os_support))
        <div class="flex items-center gap-2 text-gray-400" dir="ltr">
            @foreach (array_slice($software->os_support, 0, 4) as $os)
                <i class="{{ $osIcons[$os] ?? 'fa-solid fa-desktop' }} text-sm" title="{{ $os }}"></i>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between pt-3 border-t border-royal-gold/10 text-xs">
        <span class="text-gray-500" dir="ltr"><i class="fa-solid fa-download text-saudi-green"></i> {{ number_format($software->downloads_count) }}</span>
        <span class="font-semibold {{ $isFree ? 'text-green-600' : 'text-bronze' }}">
            @if ($isFree)
                <i class="fa-solid fa-circle-check"></i> {{ $licenseLabel }}
            @else
                {{ $licenseLabel }}
            @endif
        </span>
    </div>
</a>
