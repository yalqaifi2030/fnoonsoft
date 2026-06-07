@extends('layouts.app')

@section('title', $page->title)
@section('meta_description', $page->meta_description ?: $page->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="font-cairo font-black text-3xl mb-6">{{ $page->title }}</h1>
    <div class="card-luxury p-8 prose max-w-none">
        {!! $page->body !!}
    </div>
</div>
@endsection
