@extends('layouts.app')

@section('title', $category->name)
@section('meta_description', $category->description)

@section('content')
<div x-data="{ open: false, type: '', src: '', play(t, s) { this.type = t; this.src = s; this.open = true; } }">

    {{-- ===== HERO ===== --}}
    <section class="relative text-white overflow-hidden bg-gradient-to-br {{ $category->color }}">
        <div class="absolute inset-0 hero-grid opacity-30"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-16">
            <a href="{{ route('learn') }}" class="inline-flex items-center gap-2 text-sm text-white/80 hover:text-white mb-5">
                <i class="fa-solid fa-arrow-left rtl:rotate-180"></i> {{ __('learn.videos.back') }}
            </a>
            <div class="flex items-center gap-4">
                <span class="h-16 w-16 rounded-2xl bg-white/15 inline-flex items-center justify-center text-3xl shrink-0">
                    <i class="{{ $category->icon }}"></i>
                </span>
                <div>
                    <h1 class="font-cairo font-black text-3xl md:text-4xl">{{ $category->name }}</h1>
                    <p class="text-white/85 mt-1">{{ $category->description }}</p>
                    <span class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold">
                        <i class="fa-solid fa-video"></i> {{ $category->activeVideos->count() }} {{ __('learn.videos.lessons') }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== VIDEOS ===== --}}
    <div class="max-w-7xl mx-auto px-4 py-10">
        @if ($category->activeVideos->isEmpty())
            <div class="card-luxury p-16 text-center text-gray-400">
                <i class="fa-solid fa-film text-5xl text-gray-300"></i>
                <p class="mt-4">{{ __('learn.videos.empty') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($category->activeVideos as $video)
                    @include('partials.video-card', ['video' => $video])
                @endforeach
            </div>
        @endif
    </div>

    @include('partials.video-modal')
</div>
@endsection
