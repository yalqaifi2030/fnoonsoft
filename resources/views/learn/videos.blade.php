@extends('layouts.app')

@section('title', __('learn.videos.page_title'))

@section('content')
<div x-data="{ open: false, type: '', src: '', play(t, s) { this.type = t; this.src = s; this.open = true; } }">

    {{-- ===== HERO ===== --}}
    <section class="relative hero-pattern text-white overflow-hidden">
        <div class="absolute inset-0 hero-grid opacity-60"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-14 text-center">
            <a href="{{ route('learn') }}" class="inline-flex items-center gap-2 text-sm text-gray-300 hover:text-white mb-4">
                <i class="fa-solid fa-arrow-left rtl:rotate-180"></i> {{ __('learn.videos.back') }}
            </a>
            <h1 class="font-cairo font-black text-3xl md:text-4xl">{{ __('learn.videos.page_title') }}</h1>
            <p class="text-gray-300 mt-2">{{ __('learn.videos.subtitle') }}</p>
        </div>
        <div class="relative">
            <svg viewBox="0 0 1440 60" class="w-full h-[40px] fill-[#FBFAF6]" preserveAspectRatio="none"><path d="M0,32 C480,80 960,0 1440,32 L1440,60 L0,60 Z"></path></svg>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 py-8">
        @forelse ($categories as $category)
            {{-- ===== CATEGORY GROUP ===== --}}
            <section class="py-6">
                <div class="section-head">
                    <h2 class="font-cairo font-bold text-2xl flex items-center gap-3">
                        <span class="h-10 w-10 rounded-xl bg-gradient-to-br {{ $category->color }} text-white inline-flex items-center justify-center"><i class="{{ $category->icon }}"></i></span>
                        {{ $category->name }}
                    </h2>
                    <a href="{{ route('learn.category', $category) }}" class="view-all">
                        {{ __('site.view_all') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-xs"></i>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($category->activeVideos as $video)
                        @include('partials.video-card', ['video' => $video])
                    @endforeach
                </div>
            </section>
        @empty
            <div class="card-luxury p-16 text-center text-gray-400">
                <i class="fa-solid fa-film text-5xl text-gray-300"></i>
                <p class="mt-4">{{ __('learn.videos.empty') }}</p>
            </div>
        @endforelse
    </div>

    @include('partials.video-modal')
</div>
@endsection
