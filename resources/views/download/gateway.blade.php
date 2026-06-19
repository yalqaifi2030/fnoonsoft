@extends('layouts.app')

@section('title', __('site.download.button') . ' — ' . $software->name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center"
     x-data="{ seconds: 5, started: false, url: '{{ route('download.start', [$software, $link]) }}' }"
     x-init="
        const t = setInterval(() => {
            seconds--;
            if (seconds <= 0) { clearInterval(t); started = true; window.location.href = url; }
        }, 1000);
     ">
    <div class="card-luxury p-10">
        <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-saudi-green/10 text-saudi-green mb-5">
            <i class="fa-solid fa-cloud-arrow-down text-2xl"></i>
        </span>
        <h1 class="font-cairo font-black text-2xl">{{ $software->name }}</h1>
        <p class="text-gray-500 mt-1" dir="ltr">v{{ $software->current_version }} · {{ $link->humanSize() }}</p>

        <p class="mt-6 text-gray-600" x-show="!started">
            {{ __('site.download.starting', ['seconds' => '']) }}
            <span class="font-bold text-saudi-green text-xl" x-text="seconds"></span>
        </p>

        <a :href="url" class="btn-primary mt-6 justify-center">
            <i class="fa-solid fa-download"></i> {{ __('site.download.now') }}
        </a>

        <p class="mt-4 text-xs text-gray-400">
            {!! __('site.download.manual', ['link' => '<a href="'.route('download.start', [$software, $link]).'" class="text-saudi-green underline">'.__('site.download.click_here').'</a>']) !!}
        </p>

        <p class="mt-3 text-xs text-gray-400">
            {{ __('report.download_prompt') }}
            <button type="button"
                    @click="window.fnoonReport({ source: 'download', software: @js($software->name), softwareSlug: @js($software->slug), error: 'download gateway' })"
                    class="font-medium text-saudi-green underline">{{ __('report.download_cta') }}</button>
        </p>

        @if ($link->checksum_sha256)
            <div class="mt-6 pt-6 border-t border-royal-gold/10 text-xs text-gray-500">
                <span class="font-semibold">SHA-256:</span>
                <code class="font-mono break-all" dir="ltr">{{ $link->checksum_sha256 }}</code>
            </div>
        @endif
    </div>

    {{-- Rate the program (after the download starts) --}}
    <div class="card-luxury p-6 mt-6 text-start">
        <h2 class="font-cairo font-bold text-lg flex items-center gap-2">
            <i class="fa-solid fa-star text-royal-gold"></i> {{ __('review.gateway_title') }}
        </h2>
        <p class="text-sm text-gray-500 mt-1 mb-4">{{ __('review.gateway_hint') }}</p>
        @include('partials.review-form', ['software' => $software, 'compact' => true])
    </div>

    <x-ad placement="gateway" class="mt-6" />
</div>
@endsection
