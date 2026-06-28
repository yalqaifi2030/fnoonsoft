{{-- Horizontal list layout for software (icon · name/desc/category · platform+downloads · reputation · size).
     Vars: $title, $items, $moreUrl (optional), $ranked (optional bool). --}}
@php
    $osIcons = [
        'windows' => 'fa-brands fa-windows', 'macos' => 'fa-brands fa-apple', 'linux' => 'fa-brands fa-linux',
        'android' => 'fa-brands fa-android', 'ios' => 'fa-brands fa-apple', 'web' => 'fa-solid fa-globe',
    ];
@endphp

@if ($items->isNotEmpty())
    <section class="py-7">
        <div class="section-head">
            <h2 class="font-cairo font-bold text-2xl flex items-center gap-3">
                <span class="inline-block h-6 w-1.5 rounded-full bg-gradient-to-b from-royal-gold to-saudi-green"></span>
                {{ $title }}
            </h2>
            @isset($moreUrl)
                <a href="{{ $moreUrl }}" class="view-all">
                    {{ __('site.view_all') }}
                    <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-xs"></i>
                </a>
            @endisset
        </div>

        <div class="space-y-3">
            @foreach ($items as $software)
                @include('partials.software-list-row', ['software' => $software, 'ranked' => $ranked ?? false, 'rank' => $loop->iteration])
            @endforeach
        </div>
    </section>
@endif
