@extends('layouts.app')

@section('title', $article->meta_title ?: $article->title)
@section('meta_description', $article->meta_description ?: $article->excerpt)
@section('og_type', 'article')
@section('og_title', $article->title)
@section('og_description', $article->meta_description ?: $article->excerpt)
@section('og_image', $article->cover_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($article->cover_image) : '')

@push('head')
    <meta property="article:published_time" content="{{ $article->published_at?->toAtomString() }}">
    <meta property="article:modified_time" content="{{ ($article->updated_at ?? $article->published_at)?->toAtomString() }}">
    @if ($article->author?->name)<meta property="article:author" content="{{ $article->author->name }}">@endif
    @if ($article->category?->name)<meta property="article:section" content="{{ $article->category->name }}">@endif
@endpush

@push('jsonld')
    <script type="application/ld+json">{!! json_encode($article->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('site.nav.home'), 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('site.nav.blog'), 'item' => route('blog.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => route('blog.show', $article)],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

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
