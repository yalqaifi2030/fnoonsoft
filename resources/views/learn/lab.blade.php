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

    <div class="max-w-7xl mx-auto px-4 py-10">
        @includeIf('partials.labs.'.$lab->key)
    </div>
@endsection
