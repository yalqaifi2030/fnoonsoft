@props(['software'])

@php
    $licenseLabel = __('content.license.'.$software->license_type);
    $isFree = in_array($software->license_type, ['free', 'open_source']);
@endphp

<a href="{{ route('software.show', $software) }}"
   class="card-luxury group flex h-full flex-col gap-4 p-5">

    <div class="flex items-start gap-4">
        <div class="relative shrink-0">
            @if ($software->icon)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                     alt="{{ $software->name }}" width="96" height="96" loading="lazy" decoding="async"
                     class="h-[76px] w-[76px] rounded-2xl bg-white object-contain ring-1 ring-royal-gold/15">
            @else
                <span class="inline-flex h-[76px] w-[76px] items-center justify-center rounded-2xl bg-gradient-to-br from-saudi-green/10 to-royal-gold/10 text-saudi-green">
                    <i class="{{ $software->content_type->icon() }} text-3xl"></i>
                </span>
            @endif
            @if ($software->download_requires_login)
                {{-- Members-only file: lock tag --}}
                <span class="absolute -end-1.5 -top-1.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-saudi-green text-[11px] text-white shadow-md ring-2 ring-white"
                      title="{{ __('site.download.members_only') }}">
                    <i class="fa-solid fa-lock"></i>
                </span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <span class="mb-1 inline-flex max-w-full items-center gap-1 rounded-full bg-saudi-green/8 px-2 py-0.5 text-[10px] font-semibold text-saudi-green/90">
                <i class="{{ $software->content_type->icon() }}"></i>
                <span class="truncate">{{ $software->content_type->label() }}</span>
            </span>
            <h3 class="truncate font-cairo text-base font-bold text-luxury-black transition group-hover:text-saudi-green">{{ $software->name }}</h3>
            <p class="truncate text-xs text-gray-500">{{ $software->developer?->name }}</p>
            <div class="mt-1 flex items-center gap-1 text-xs text-royal-gold" dir="ltr">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="fa-{{ $i <= round($software->rating_avg) ? 'solid' : 'regular' }} fa-star"></i>
                @endfor
                <span class="ms-1 text-gray-400">{{ $software->rating_avg }}</span>
            </div>
        </div>
    </div>

    <p class="flex-1 text-sm leading-relaxed text-gray-600 line-clamp-2">{{ $software->short_description }}</p>

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

    <div class="flex items-center justify-between border-t border-royal-gold/10 pt-3 text-xs">
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
