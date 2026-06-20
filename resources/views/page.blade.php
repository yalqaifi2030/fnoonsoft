@extends('layouts.app')

@section('title', $page->title)
@section('meta_description', $page->meta_description ?: $page->title)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">

    {{-- Breadcrumb --}}
    <nav class="mb-5 flex items-center gap-2 text-xs text-gray-400" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="transition hover:text-saudi-green">{{ __('site.nav.home') }}</a>
        <span class="opacity-50">/</span>
        <span class="truncate text-gray-600">{{ $page->title }}</span>
    </nav>

    {{-- Header --}}
    <div class="card-luxury relative mb-5 overflow-hidden p-0">
        <div class="absolute inset-0 bg-gradient-to-br from-saudi-green to-[#00582b]"></div>
        <div class="absolute -end-10 -top-10 h-36 w-36 rounded-full bg-white/5"></div>
        <div class="relative px-7 py-7 text-white">
            <h1 class="font-cairo text-2xl font-black sm:text-3xl">{{ $page->title }}</h1>
            <p class="mt-1 text-sm text-white/70">{{ config('app.name') }}</p>
        </div>
    </div>

    {{-- Body --}}
    <article class="card-luxury prose prose-headings:font-cairo prose-headings:text-luxury-black prose-a:text-saudi-green max-w-none p-7 sm:p-9
                    prose-h2:text-xl prose-h2:mt-7 prose-h2:mb-3 prose-p:leading-relaxed prose-li:marker:text-saudi-green">
        {!! $page->body !!}
    </article>
</div>
@endsection
