@extends('layouts.app')

@section('title', __('formats.title'))
@section('meta_description', __('formats.subtitle'))

@php
    $familyIcon = [
        'autodesk' => 'fa-solid fa-drafting-compass',
        'adobe' => 'fa-solid fa-bezier-curve',
        'rhino' => 'fa-solid fa-cube',
        'lumion' => 'fa-solid fa-mountain-sun',
        'other' => 'fa-solid fa-shapes',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">

    <nav class="mb-5 flex items-center gap-2 text-xs text-gray-400" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="transition hover:text-saudi-green">{{ __('site.nav.home') }}</a>
        <span class="opacity-50">/</span>
        <span class="text-gray-600">{{ __('formats.title') }}</span>
    </nav>

    <div class="mb-8 text-center">
        <h1 class="font-cairo text-3xl font-black sm:text-4xl">{{ __('formats.title') }}</h1>
        <p class="mt-3 mx-auto max-w-2xl text-gray-500">{{ __('formats.subtitle') }}</p>
    </div>

    @forelse ($groups as $family => $formats)
        <section class="mb-10">
            <h2 class="mb-4 flex items-center gap-2 font-cairo text-xl font-bold">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-saudi-green/10 text-saudi-green">
                    <i class="{{ $familyIcon[$family] ?? 'fa-solid fa-shapes' }}"></i>
                </span>
                {{ __('formats.fam.'.$family) }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($formats as $format)
                    @php($c = $format->badgeColor())
                    <div id="{{ $format->extension }}" class="card-luxury scroll-mt-28 p-5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 min-w-[3rem] items-center justify-center rounded-xl px-2 text-sm font-extrabold text-white" style="background: {{ $c }}" dir="ltr">{{ $format->ext() }}</span>
                            <div class="min-w-0">
                                <div class="truncate font-bold text-luxury-black">{{ $format->name }}</div>
                                @if ($format->description)
                                    <div class="truncate text-xs text-gray-400">{{ $format->description }}</div>
                                @endif
                            </div>
                        </div>

                        @if ($format->software->isNotEmpty())
                            <div class="mt-4 border-t border-royal-gold/10 pt-3">
                                <div class="mb-1.5 text-[11px] font-semibold text-gray-400">{{ __('formats.used_by') }}</div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($format->software->take(6) as $s)
                                        <a href="{{ route('software.show', $s) }}"
                                           class="max-w-[160px] truncate rounded-lg bg-gray-50 px-2 py-1 text-xs text-gray-600 ring-1 ring-gray-100 transition hover:text-saudi-green hover:ring-saudi-green/30">{{ $s->name }}</a>
                                    @endforeach
                                    @if ($format->software->count() > 6)
                                        <span class="rounded-lg px-2 py-1 text-xs text-gray-400">{{ __('formats.more', ['count' => $format->software->count() - 6]) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card-luxury p-12 text-center text-gray-400">{{ __('formats.empty') }}</div>
    @endforelse
</div>
@endsection
