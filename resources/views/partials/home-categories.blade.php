{{-- Categories panel (with per-category program counts) shown beside the homepage lists. --}}
@if ($categories->isNotEmpty())
    <div class="card-luxury p-5 lg:sticky lg:top-20">
        <h3 class="mb-3 flex items-center gap-2 font-cairo text-lg font-black text-luxury-black">
            <span class="inline-block h-5 w-1.5 rounded-full bg-gradient-to-b from-royal-gold to-saudi-green"></span>
            {{ __('site.sections.categories') }}
        </h3>
        <ul class="space-y-0.5">
            @foreach ($categories as $cat)
                <li>
                    <a href="{{ route('browse', ['category' => $cat->slug]) }}"
                       class="group flex items-center justify-between gap-2 rounded-xl px-3 py-2 transition hover:bg-saudi-green/5">
                        <span class="flex min-w-0 items-center gap-2 text-sm text-gray-700 transition group-hover:text-saudi-green">
                            @if ($cat->icon)<i class="{{ $cat->icon }} w-4 text-center text-saudi-green/70"></i>@endif
                            <span class="truncate font-medium">{{ $cat->name }}</span>
                        </span>
                        <span class="shrink-0 rounded-full bg-saudi-green/10 px-2 py-0.5 text-xs font-bold text-saudi-green transition group-hover:bg-saudi-green group-hover:text-white" dir="ltr">{{ number_format($cat->count) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('browse') }}" class="mt-3 flex items-center justify-center gap-1 text-sm font-bold text-saudi-green hover:underline">
            {{ __('site.view_all') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-xs"></i>
        </a>
    </div>
@endif
