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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($items as $software)
                <x-software-card :software="$software" />
            @endforeach
        </div>
    </section>
@endif
