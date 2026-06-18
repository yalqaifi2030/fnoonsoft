{{-- Reusable star-rating form. Expects $software. Optional $compact (smaller). --}}
@php($compact = $compact ?? false)

<div x-data="{
        rating: 0,
        hover: 0,
        sent: false,
        get label() {
            const r = this.hover || this.rating;
            return r ? @js([__('review.stars.1'),__('review.stars.2'),__('review.stars.3'),__('review.stars.4'),__('review.stars.5')])[r-1] : '';
        }
     }">
    @if (session('review_status'))
        <div class="mb-3 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-700">
            <i class="fa-solid fa-circle-check"></i> {{ session('review_status') }}
        </div>
    @endif
    @error('rating')
        <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('reviews.store', $software) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="rating" :value="rating">

        {{-- Stars --}}
        <div class="flex items-center gap-3">
            <div class="flex gap-1" dir="ltr">
                <template x-for="s in 5" :key="s">
                    <button type="button" @click="rating = s" @mouseenter="hover = s" @mouseleave="hover = 0"
                            class="text-3xl leading-none transition"
                            :class="(hover || rating) >= s ? 'text-royal-gold' : 'text-gray-300'"
                            aria-label="rate">★</button>
                </template>
            </div>
            <span class="text-sm font-semibold text-bronze" x-text="label"></span>
        </div>

        @auth
            <input type="hidden" name="author_name" value="{{ auth()->user()->displayName() }}">
        @else
            <input type="text" name="author_name" required maxlength="80"
                   placeholder="{{ __('review.your_name') }}"
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">
        @endauth

        @unless ($compact)
            <input type="text" name="title" maxlength="160"
                   placeholder="{{ __('review.title_ph') }}"
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">
        @endunless

        <textarea name="body" maxlength="2000" rows="{{ $compact ? 2 : 3 }}"
                  placeholder="{{ __('review.body_ph') }}"
                  class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none"></textarea>

        {{-- honeypot --}}
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

        <button type="submit" :disabled="!rating"
                class="btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fa-solid fa-star"></i> {{ __('review.submit') }}
        </button>
        <p class="text-[11px] text-gray-400">{{ __('review.moderation_note') }}</p>
    </form>
</div>
