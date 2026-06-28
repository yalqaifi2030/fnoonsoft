{{-- Categories panel (card style, stacked vertically) shown beside the homepage lists.
     Each card: icon + name + program count. --}}
@if ($categories->isNotEmpty())
    <div>
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="flex items-center gap-2 font-cairo text-lg font-black text-luxury-black">
                <span class="inline-block h-5 w-1.5 rounded-full bg-gradient-to-b from-royal-gold to-saudi-green"></span>
                {{ __('site.sections.categories') }}
            </h3>
            <a href="{{ route('browse') }}" class="shrink-0 text-xs font-bold text-saudi-green hover:underline">
                {{ __('site.view_all') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-[10px]"></i>
            </a>
        </div>

        <div class="space-y-3">
            @foreach ($categories as $cat)
                <a href="{{ route('browse', ['category' => $cat->slug]) }}"
                   class="card-luxury group relative flex flex-col items-center gap-2 p-4 text-center transition hover:border-royal-gold/40">
                    <span class="absolute end-2.5 top-2.5 rounded-full bg-saudi-green/10 px-2 py-0.5 text-[11px] font-bold text-saudi-green transition group-hover:bg-saudi-green group-hover:text-white" dir="ltr">{{ number_format($cat->count) }}</span>
                    <i class="{{ $cat->icon ?? 'fa-solid fa-folder' }} text-2xl text-saudi-green transition group-hover:scale-110"></i>
                    <span class="w-full truncate text-sm font-semibold leading-tight text-luxury-black transition group-hover:text-saudi-green">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
