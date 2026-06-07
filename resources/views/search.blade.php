@extends('layouts.app')

@section('title', __('site.hero.search_button') . ': ' . $term)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <form action="{{ route('search') }}" method="GET" class="mb-8 flex gap-2 max-w-2xl">
        <input name="q" value="{{ $term }}" placeholder="{{ __('site.hero.search_placeholder') }}"
               class="flex-1 rounded-xl border-gray-200 px-5 py-3 focus:ring-2 focus:ring-royal-gold">
        <button class="btn-primary px-6"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    @if ($results === null)
        <p class="text-gray-400">{{ __('site.hero.search_placeholder') }}</p>
    @elseif ($results->isEmpty())
        <div class="card-luxury p-12 text-center text-gray-400">{{ __('site.empty') }}</div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($results as $item)
                <x-software-card :software="$item" />
            @endforeach
        </div>
        <div class="mt-8">{{ $results->links() }}</div>
    @endif
</div>
@endsection
