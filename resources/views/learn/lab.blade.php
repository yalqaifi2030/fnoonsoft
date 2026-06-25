@extends('layouts.app')

@section('title', $lab->title)
@section('meta_description', $lab->description)

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="relative text-white overflow-hidden bg-gradient-to-br {{ $lab->color }}">
        <div class="absolute inset-0 hero-grid opacity-30"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-12">
            <a href="{{ route('learn') }}" class="inline-flex items-center gap-2 text-sm text-white/80 hover:text-white mb-5">
                <i class="fa-solid fa-arrow-left rtl:rotate-180"></i> {{ __('learn.videos.back') }}
            </a>
            <div class="flex items-center gap-4">
                <span class="h-16 w-16 rounded-2xl bg-white/15 inline-flex items-center justify-center text-3xl shrink-0"><i class="{{ $lab->icon }}"></i></span>
                <div>
                    <h1 class="font-cairo font-black text-3xl md:text-4xl">{{ $lab->title }}</h1>
                    <p class="text-white/85 mt-1 max-w-2xl">{{ $lab->description }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="lab-surface max-w-7xl mx-auto px-4 py-10">
        {{-- Custom hand-built labs use their own partial; anything else (admin-created)
             falls back to the generic, data-driven block renderer. --}}
        @if (view()->exists('partials.labs.'.$lab->key))
            @include('partials.labs.'.$lab->key)
        @else
            @include('partials.labs._blocks')
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /* Tailwind Preflight zeroes border-width, so lab inputs that only set a
           border *colour* render borderless. Force every lab text/number/search
           input and select to the same clean field look used in the admin panel
           (Filament): light border, rounded-lg, white bg, soft shadow, brand-
           coloured focus ring. `.lab-surface :is(...)` outranks the per-input
           utility classes so the look is uniform across all labs. */
        .lab-surface :is(input[type="text"],
                         input[type="number"],
                         input[type="search"],
                         input[type="password"],
                         input[type="email"],
                         input[type="tel"],
                         input[type="url"],
                         input:not([type]),
                         select) {
            border: 1px solid #e5e7eb;            /* gray-200 — subtle, like the panel */
            background-color: #fff;
            border-radius: .5rem;                  /* rounded-lg */
            padding: .55rem .75rem;
            min-height: 2.5rem;
            font-size: .875rem;
            line-height: 1.5;
            color: #111827;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / .05);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .lab-surface :is(input[type="text"], input[type="number"], input[type="search"], input[type="password"], input[type="email"], input[type="tel"], input[type="url"], select):focus {
            outline: none;
            border-color: rgb(var(--c-primary) / 1);
            box-shadow: 0 0 0 3px rgb(var(--c-primary) / .15);
        }
        .lab-surface input::placeholder { color: #9ca3af; }
        .lab-surface input[readonly] { background-color: #f9fafb; color: #4b5563; }  /* gray-50 */
        /* Custom dropdown chevron like the admin panel (replaces the native arrow). */
        .lab-surface select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .7rem center;
            background-size: 1.05rem 1.05rem;
            padding-inline-start: .75rem;
            padding-inline-end: 2.25rem;
        }
        .lab-surface select:focus { border-color: rgb(var(--c-primary) / 1); }
        .lab-surface select::-ms-expand { display: none; }
        /* Range sliders keep their native control, not the field box. */
        .lab-surface input[type="range"] {
            width: auto; min-height: 0; border: 0; box-shadow: none; padding: 0;
            background: transparent; accent-color: rgb(var(--c-primary) / 1);
        }
    </style>
@endpush
