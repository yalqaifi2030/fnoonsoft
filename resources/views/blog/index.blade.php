@extends('layouts.app')

@section('title', __('site.nav.blog'))

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <h1 class="font-cairo font-black text-3xl mb-8">{{ __('site.nav.blog') }}</h1>

    @if ($articles->isEmpty())
        <div class="card-luxury p-12 text-center text-gray-400">{{ __('site.empty') }}</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($articles as $article)
                <a href="{{ route('blog.show', $article) }}" class="card-luxury overflow-hidden group">
                    @if ($article->cover_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}"
                             alt="{{ $article->title }}" class="h-44 w-full object-cover">
                    @endif
                    <div class="p-5">
                        <h2 class="font-cairo font-bold text-lg group-hover:text-saudi-green">{{ $article->title }}</h2>
                        <p class="text-sm text-gray-500 mt-2 line-clamp-3">{{ $article->excerpt }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $articles->links() }}</div>
    @endif
</div>
@endsection
