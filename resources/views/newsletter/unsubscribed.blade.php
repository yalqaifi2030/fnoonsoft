@extends('layouts.app')

@section('title', __('newsletter.unsub_done'))

@section('content')
<div class="mx-auto max-w-lg px-4 py-20 text-center">
    <div class="card-luxury p-10">
        @if ($ok)
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                <i class="fa-solid fa-circle-check text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.unsub_done') }}</h1>
            <p class="mt-2 text-gray-500">{{ __('newsletter.unsub_body') }}</p>
        @else
            <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                <i class="fa-solid fa-link-slash text-2xl"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black">{{ __('newsletter.unsub_invalid') }}</h1>
        @endif

        <a href="{{ route('home') }}" class="btn-primary mt-7 inline-flex">
            <i class="fa-solid fa-house"></i> {{ __('site.nav.home') }}
        </a>
    </div>
</div>
@endsection
