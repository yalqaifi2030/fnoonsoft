{{-- "Recommended for you" — injected by the interest tracker on the homepage.
     Vars: $items (Collection<Software>). --}}
<section class="py-7">
    <div class="section-head">
        <h2 class="flex items-center gap-3 font-cairo text-2xl font-bold">
            <span class="inline-block h-6 w-1.5 rounded-full bg-gradient-to-b from-royal-gold to-saudi-green"></span>
            <i class="fa-solid fa-wand-magic-sparkles text-royal-gold"></i>
            {{ __('site.sections.recommended') }}
        </h2>
        <button type="button" onclick="window.fnoonInterest && window.fnoonInterest.clear()"
                class="view-all" title="{{ __('site.recommended_reset') }}">
            <i class="fa-solid fa-rotate-left text-xs"></i> {{ __('site.recommended_reset') }}
        </button>
    </div>

    <div class="space-y-3">
        @foreach ($items as $software)
            @include('partials.software-list-row', ['software' => $software])
        @endforeach
    </div>

    <p class="mt-3 text-xs text-gray-400">
        <i class="fa-solid fa-lock text-[10px]"></i> {{ __('site.recommended_hint') }}
    </p>
</section>
