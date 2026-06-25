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
           border *colour* render borderless. Give every lab text/number/search
           input and select a proper field look. :where() keeps specificity at 0
           so per-input utility classes (padding, width, rounded, bg) still win. */
        :where(.lab-surface input[type="text"],
               .lab-surface input[type="number"],
               .lab-surface input[type="search"],
               .lab-surface input:not([type]),
               .lab-surface select) {
            border: 1px solid #d1d5db;
            background-color: #fff;
            border-radius: .75rem;
            padding: .5rem .75rem;
            font-size: .875rem;
            line-height: 1.4;
            color: #1f2937;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / .05);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .lab-surface input[type="text"]:focus,
        .lab-surface input[type="number"]:focus,
        .lab-surface input[type="search"]:focus,
        .lab-surface select:focus {
            outline: none;
            border-color: rgb(var(--c-primary) / 1);
            box-shadow: 0 0 0 3px rgb(var(--c-primary) / .2);
        }
        /* Range sliders keep their native look but pick up the brand colour. */
        .lab-surface input[type="range"] { accent-color: rgb(var(--c-primary) / 1); }
    </style>
@endpush
