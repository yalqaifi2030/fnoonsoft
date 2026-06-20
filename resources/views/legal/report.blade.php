@extends('layouts.app')

@section('title', __('legal.'.$type.'.title'))
@section('meta_description', __('legal.'.$type.'.intro'))

@php
    $inputCls = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400 shadow-sm transition focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none';
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">

    <nav class="mb-5 flex items-center gap-2 text-xs text-gray-400" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="transition hover:text-saudi-green">{{ __('site.nav.home') }}</a>
        <span class="opacity-50">/</span>
        <span class="text-gray-600">{{ __('legal.'.$type.'.title') }}</span>
    </nav>

    {{-- Header --}}
    <div class="card-luxury relative mb-5 overflow-hidden p-0">
        <div class="absolute inset-0 bg-gradient-to-br from-saudi-green to-[#00582b]"></div>
        <div class="relative px-7 py-7 text-white">
            <span class="mb-2 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-lg">
                <i class="fa-solid {{ $type === 'dmca' ? 'fa-copyright' : 'fa-flag' }}"></i>
            </span>
            <h1 class="font-cairo text-2xl font-black sm:text-3xl">{{ __('legal.'.$type.'.title') }}</h1>
        </div>
    </div>

    {{-- Info --}}
    <article class="card-luxury prose prose-headings:font-cairo prose-a:text-saudi-green mb-6 max-w-none p-7 prose-li:marker:text-saudi-green">
        <p>{{ __('legal.'.$type.'.intro') }}</p>
        <ul>
            @foreach (__('legal.'.$type.'.points') as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    </article>

    {{-- Form --}}
    <div class="card-luxury p-6 sm:p-8">
        <h2 class="mb-4 flex items-center gap-2 font-cairo text-lg font-bold">
            <i class="fa-solid fa-paper-plane text-saudi-green"></i> {{ __('legal.form_title') }}
        </h2>

        @if (session('status'))
            <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('legal.report') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('legal.field.name') }} <span class="text-red-500">*</span></label>
                    <input name="name" value="{{ old('name') }}" required class="{{ $inputCls }} @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('legal.field.email') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required dir="ltr" class="{{ $inputCls }} @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('legal.field.url') }} <span class="text-red-500">*</span></label>
                <input name="url" value="{{ old('url') }}" required dir="ltr" placeholder="https://finunsoft.com/…" class="{{ $inputCls }} @error('url') border-red-400 @enderror">
                @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('legal.'.$type.'.details_label') }} <span class="text-red-500">*</span></label>
                <textarea name="details" rows="5" required placeholder="{{ __('legal.'.$type.'.details_ph') }}" class="{{ $inputCls }} resize-y @error('details') border-red-400 @enderror">{{ old('details') }}</textarea>
                @error('details') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-2 text-sm text-gray-600">
                <input type="checkbox" name="agree" value="1" required class="mt-0.5 h-4 w-4 rounded border-gray-300 text-saudi-green focus:ring-saudi-green/30">
                <span>{{ __('legal.'.$type.'.agree') }}</span>
            </label>
            @error('agree') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <button class="btn-primary w-full justify-center">
                <i class="fa-solid fa-paper-plane"></i> {{ __('legal.submit') }}
            </button>
        </form>
    </div>
</div>
@endsection
