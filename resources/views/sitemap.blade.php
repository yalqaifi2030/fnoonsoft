@extends('layouts.app')

@section('title', __('pages.sitemap.title'))
@section('meta_description', __('pages.sitemap.subtitle'))

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10">

    <div class="mb-8 text-center">
        <h1 class="font-cairo text-3xl font-black sm:text-4xl">{{ __('pages.sitemap.title') }}</h1>
        <p class="mt-3 text-gray-500">{{ __('pages.sitemap.subtitle') }}</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">

        {{-- Main sections --}}
        <div class="card-luxury p-6">
            <h2 class="mb-3 flex items-center gap-2 font-cairo font-bold"><i class="fa-solid fa-compass text-saudi-green"></i> {{ __('pages.sitemap.main') }}</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-gray-600 hover:text-saudi-green">{{ __('site.nav.home') }}</a></li>
                <li><a href="{{ route('browse') }}" class="text-gray-600 hover:text-saudi-green">{{ __('site.nav.browse') }}</a></li>
                <li><a href="{{ route('blog.index') }}" class="text-gray-600 hover:text-saudi-green">{{ __('site.nav.blog') }}</a></li>
                <li><a href="{{ route('learn') }}" class="text-gray-600 hover:text-saudi-green">{{ __('nav.group.learn') }}</a></li>
                <li><a href="{{ route('contact') }}" class="text-gray-600 hover:text-saudi-green">{{ __('site.nav.contact') }}</a></li>
            </ul>
        </div>

        {{-- Info / legal pages --}}
        <div class="card-luxury p-6">
            <h2 class="mb-3 flex items-center gap-2 font-cairo font-bold"><i class="fa-solid fa-circle-info text-saudi-green"></i> {{ __('pages.sitemap.info') }}</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($pages as $p)
                    <li><a href="{{ url('/'.$p->slug) }}" class="text-gray-600 hover:text-saudi-green">{{ $p->title }}</a></li>
                @endforeach
                <li><a href="{{ url('/dmca') }}" class="text-gray-600 hover:text-saudi-green">{{ __('legal.dmca.title') }}</a></li>
                <li><a href="{{ url('/abuse') }}" class="text-gray-600 hover:text-saudi-green">{{ __('legal.abuse.title') }}</a></li>
            </ul>
        </div>

        {{-- Categories --}}
        @if ($categories->isNotEmpty())
            <div class="card-luxury p-6">
                <h2 class="mb-3 flex items-center gap-2 font-cairo font-bold"><i class="fa-solid fa-layer-group text-saudi-green"></i> {{ __('pages.sitemap.categories') }}</h2>
                <ul class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    @foreach ($categories as $c)
                        <li><a href="{{ route('browse', ['category' => $c->slug]) }}" class="text-gray-600 hover:text-saudi-green">{{ $c->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Latest software --}}
        @if ($software->isNotEmpty())
            <div class="card-luxury p-6">
                <h2 class="mb-3 flex items-center gap-2 font-cairo font-bold"><i class="fa-solid fa-cube text-saudi-green"></i> {{ __('pages.sitemap.latest') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($software as $s)
                        <li><a href="{{ route('software.show', $s) }}" class="line-clamp-1 text-gray-600 hover:text-saudi-green">{{ $s->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Latest articles --}}
        @if ($articles->isNotEmpty())
            <div class="card-luxury p-6 md:col-span-2">
                <h2 class="mb-3 flex items-center gap-2 font-cairo font-bold"><i class="fa-solid fa-newspaper text-saudi-green"></i> {{ __('pages.sitemap.articles') }}</h2>
                <ul class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                    @foreach ($articles as $a)
                        <li><a href="{{ route('blog.show', $a) }}" class="line-clamp-1 text-gray-600 hover:text-saudi-green">{{ $a->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>
@endsection
