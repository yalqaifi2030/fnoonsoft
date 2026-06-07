@extends('layouts.app')

@section('title', __('learn.nav'))
@section('meta_description', __('learn.hero.subtitle'))

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="relative hero-pattern text-white overflow-hidden">
        <div class="absolute inset-0 hero-grid opacity-60"></div>
        <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-royal-gold/10 blur-3xl float-anim"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-20 text-center fade-up">
            <span class="chip mx-auto mb-6"><i class="fa-solid fa-graduation-cap text-royal-gold"></i> {{ __('learn.hero.badge') }}</span>
            <h1 class="font-cairo font-black text-4xl md:text-5xl leading-tight max-w-3xl mx-auto">{{ __('learn.hero.title') }}</h1>
            <p class="mt-5 text-gray-300 text-lg max-w-2xl mx-auto">{{ __('learn.hero.subtitle') }}</p>
            <a href="#labs" class="btn-gold mt-8 justify-center"><i class="fa-solid fa-flask"></i> {{ __('learn.hero.cta') }}</a>
        </div>
        <div class="relative">
            <svg viewBox="0 0 1440 60" class="w-full h-[40px] fill-[#FBFAF6]" preserveAspectRatio="none"><path d="M0,32 C480,80 960,0 1440,32 L1440,60 L0,60 Z"></path></svg>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4">

        {{-- ===== INTERACTIVE LABS (each has its own page, managed from admin) ===== --}}
        @if ($labs->isNotEmpty())
            <section id="labs" class="py-12 scroll-mt-20">
                <div class="text-center mb-10">
                    <h2 class="font-cairo font-black text-3xl">{{ __('learn.labs.title') }}</h2>
                    <p class="text-gray-500 mt-2">{{ __('learn.labs.subtitle') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($labs as $lab)
                        <a href="{{ route('learn.lab', $lab) }}" class="type-card group">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br {{ $lab->color }} text-white text-2xl shadow-lg"><i class="{{ $lab->icon }}"></i></span>
                            <h3 class="font-cairo font-bold text-lg mt-4 text-luxury-black group-hover:text-saudi-green">{{ $lab->title }}</h3>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $lab->description }}</p>
                            <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-saudi-green">
                                {{ __('learn.labs.open') }} <i class="fa-solid fa-arrow-left rtl:rotate-0 ltr:rotate-180 text-xs"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
            <div class="gold-divider"></div>
        @endif

        {{-- ===== LEARNING TRACKS ===== --}}
        @if ($categories->isNotEmpty())
            <section class="py-12">
                <div class="text-center mb-10">
                    <h2 class="font-cairo font-black text-3xl">{{ __('learn.tracks.title') }}</h2>
                    <p class="text-gray-500 mt-2">{{ __('learn.tracks.subtitle') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($categories as $cat)
                        <a href="{{ route('learn.category', $cat) }}" class="type-card group">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br {{ $cat->color }} text-white text-2xl shadow-lg"><i class="{{ $cat->icon }}"></i></span>
                            <h3 class="font-cairo font-bold text-lg mt-4 text-luxury-black group-hover:text-saudi-green">{{ $cat->name }}</h3>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $cat->description }}</p>
                            <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-saudi-green"><i class="fa-solid fa-video"></i> {{ $cat->videos_count }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ===== VIDEOS CTA ===== --}}
        <section class="py-12 text-center">
            <h2 class="font-cairo font-black text-3xl">{{ __('learn.videos.title') }}</h2>
            <p class="text-gray-500 mt-2 mb-6">{{ __('learn.videos.subtitle') }}</p>
            <a href="{{ route('learn.videos') }}" class="btn-primary"><i class="fa-solid fa-photo-film"></i> {{ __('learn.videos.browse') }}</a>
        </section>
    </div>
@endsection
