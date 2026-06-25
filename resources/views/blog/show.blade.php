@extends('layouts.app')

@section('title', $article->title)
@section('meta_description', $article->excerpt)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="font-cairo font-black text-3xl mb-3">{{ $article->title }}</h1>
    <p class="text-sm text-gray-500 mb-6">
        {{ $article->author?->name }} · <span dir="ltr">{{ $article->published_at?->format('Y-m-d') }}</span>
    </p>
    @if ($article->cover_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) }}"
             alt="{{ $article->title }}" class="rounded-2xl w-full mb-8">
    @endif
    <div class="card-luxury p-8 prose max-w-none">
        {!! $article->body !!}
    </div>

    <div class="mt-6">
        <x-share :title="$article->title" :url="route('blog.show', $article)" />
    </div>
</div>
@endsection
