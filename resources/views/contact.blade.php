@extends('layouts.app')

@section('title', __('contact.title'))

@php
    use App\Models\Setting;
    $email = Setting::get('contact_email');
    $phone = Setting::get('contact_phone');
@endphp

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <h1 class="font-cairo font-black text-3xl mb-2">{{ __('contact.title') }}</h1>

    {{-- Contact info (managed from admin → Site settings) --}}
    @if ($email || $phone)
        <div class="flex flex-wrap gap-4 mb-6 text-sm text-gray-600" dir="ltr">
            @if ($email)
                <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 hover:text-saudi-green">
                    <i class="fa-solid fa-envelope text-saudi-green"></i> {{ $email }}
                </a>
            @endif
            @if ($phone)
                <a href="tel:{{ $phone }}" class="inline-flex items-center gap-2 hover:text-saudi-green">
                    <i class="fa-solid fa-phone text-saudi-green"></i> {{ $phone }}
                </a>
            @endif
        </div>
    @endif

    <form action="{{ route('contact.store') }}" method="POST" class="card-luxury p-8 space-y-4">
        @csrf
        {{-- honeypot --}}
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

        <div>
            <label class="block text-sm font-semibold mb-1">{{ __('contact.name') }}</label>
            <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-200">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">{{ __('contact.email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border-gray-200" dir="ltr">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">{{ __('contact.subject') }}</label>
            <input name="subject" value="{{ old('subject') }}" class="w-full rounded-lg border-gray-200">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">{{ __('contact.message') }}</label>
            <textarea name="message" rows="5" required class="w-full rounded-lg border-gray-200">{{ old('message') }}</textarea>
            @error('message') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button class="btn-primary">{{ __('contact.send') }}</button>
    </form>

    {{-- FAQ accordion (managed from admin → FAQs) --}}
    @if ($faqs->isNotEmpty())
        <div class="mt-12">
            <h2 class="font-cairo font-bold text-2xl mb-5 text-center">{{ __('site.faq.title') }}</h2>
            <div class="space-y-3" x-data="{ open: null }">
                @foreach ($faqs as $i => $faq)
                    <div class="card-luxury overflow-hidden">
                        <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                class="w-full flex items-center justify-between gap-3 p-4 text-start font-semibold">
                            <span>{{ $faq->question }}</span>
                            <i class="fa-solid fa-chevron-down text-saudi-green text-sm transition-transform"
                               :class="{ 'rotate-180': open === {{ $i }} }"></i>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                            {!! $faq->answer !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
