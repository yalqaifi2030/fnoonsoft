@extends('layouts.app')

@php($mode = $mode ?? 'done')

@section('title', $mode === 'confirmed' ? __('newsletter.confirmed_title') : __('newsletter.unsub_done'))

@section('content')
<div class="mx-auto max-w-lg px-4 py-20 text-center">
    <div class="card-luxury p-10">
        @if (! $ok)
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                <i class="fa-solid fa-link-slash text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.unsub_invalid') }}</h1>
        @elseif ($mode === 'confirmed')
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                <i class="fa-solid fa-envelope-circle-check text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.confirmed_title') }}</h1>
            <p class="mt-2 text-gray-500">{{ __('newsletter.confirmed_body') }}</p>
        @elseif ($mode === 'ask')
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <i class="fa-solid fa-envelope-open text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.unsub_ask') }}</h1>
            <form method="POST" action="{{ route('newsletter.unsubscribe.do', $token) }}" class="mt-6">
                @csrf
                <button type="submit" class="btn-primary inline-flex">
                    <i class="fa-solid fa-bell-slash"></i> {{ __('newsletter.unsubscribe') }}
                </button>
            </form>
        @else
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                <i class="fa-solid fa-circle-check text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.unsub_done') }}</h1>
            <p class="mt-2 text-gray-500">{{ __('newsletter.unsub_body') }}</p>
        @endif

        <a href="{{ route('home') }}" class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-saudi-green">
            <i class="fa-solid fa-house"></i> {{ __('site.nav.home') }}
        </a>
    </div>
</div>
@endsection
