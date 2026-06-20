@extends('layouts.app')

@section('title', __('contact.title'))

@php
    use App\Models\Setting;
    $email = Setting::get('contact_email');
    $phone = Setting::get('contact_phone');
    // Professional, visible input style (the public site uses the Tailwind CDN,
    // so border/padding/focus utilities all apply).
    $inputCls = 'w-full rounded-xl border border-gray-300 bg-white py-2.5 ps-11 pe-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm transition focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none';
@endphp

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">

    {{-- Header --}}
    <div class="mb-8 text-center">
        <h1 class="font-cairo font-black text-3xl sm:text-4xl">{{ __('contact.title') }}</h1>
        <p class="mt-3 mx-auto max-w-xl text-gray-500">{{ __('contact.subtitle') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Form --}}
        <div class="lg:col-span-3">
            <form action="{{ route('contact.store') }}" method="POST" class="card-luxury p-6 sm:p-8">
                @csrf
                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                @if (session('status'))
                    <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                        <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
                    </div>
                @endif

                <div class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('contact.name') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400"><i class="fa-solid fa-user text-sm"></i></span>
                            <input name="name" value="{{ old('name') }}" required placeholder="{{ __('contact.name_ph') }}"
                                   class="{{ $inputCls }} @error('name') border-red-400 @enderror">
                        </div>
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('contact.email') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400"><i class="fa-solid fa-envelope text-sm"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" required dir="ltr" placeholder="{{ __('contact.email_ph') }}"
                                   class="{{ $inputCls }} text-start @error('email') border-red-400 @enderror">
                        </div>
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Subject --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('contact.subject') }} <span class="text-xs font-normal text-gray-400">({{ __('contact.optional') }})</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-gray-400"><i class="fa-solid fa-tag text-sm"></i></span>
                            <input name="subject" value="{{ old('subject') }}" placeholder="{{ __('contact.subject_ph') }}"
                                   class="{{ $inputCls }} @error('subject') border-red-400 @enderror">
                        </div>
                        @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Message --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">{{ __('contact.message') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute start-0 top-0 flex items-center ps-3.5 pt-3 text-gray-400"><i class="fa-solid fa-comment-dots text-sm"></i></span>
                            <textarea name="message" rows="6" required placeholder="{{ __('contact.message_ph') }}"
                                      class="{{ $inputCls }} resize-y pt-3 @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                        </div>
                        @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button class="btn-primary mt-6 w-full justify-center text-base">
                    <i class="fa-solid fa-paper-plane"></i> {{ __('contact.send') }}
                </button>
            </form>
        </div>

        {{-- Info aside --}}
        <aside class="lg:col-span-2">
            <div class="card-luxury relative overflow-hidden p-0">
                <div class="bg-gradient-to-br from-saudi-green to-[#00582b] p-6 text-white">
                    <span class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-xl">
                        <i class="fa-solid fa-headset"></i>
                    </span>
                    <h2 class="font-cairo text-lg font-bold">{{ __('contact.info_title') }}</h2>
                    <p class="mt-1 text-sm text-white/80">{{ __('contact.info_hint') }}</p>
                </div>

                <div class="space-y-3 p-6">
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3 transition hover:border-saudi-green/30 hover:bg-saudi-green/5">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-saudi-green/10 text-saudi-green"><i class="fa-solid fa-envelope"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs text-gray-400">{{ __('contact.email') }}</span>
                                <span class="block truncate text-sm font-semibold text-gray-700" dir="ltr">{{ $email }}</span>
                            </span>
                        </a>
                    @endif
                    @if ($phone)
                        <a href="tel:{{ $phone }}" class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3 transition hover:border-saudi-green/30 hover:bg-saudi-green/5">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-saudi-green/10 text-saudi-green"><i class="fa-solid fa-phone"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs text-gray-400">{{ __('contact.phone') }}</span>
                                <span class="block truncate text-sm font-semibold text-gray-700" dir="ltr">{{ $phone }}</span>
                            </span>
                        </a>
                    @endif

                    <div class="flex items-start gap-3 rounded-xl bg-royal-gold/10 px-4 py-3 text-sm text-gray-600">
                        <i class="fa-solid fa-clock mt-0.5 text-royal-gold"></i>
                        <span>{{ __('contact.response_note') }}</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    {{-- FAQ accordion (managed from admin → FAQs) --}}
    @if ($faqs->isNotEmpty())
        <div class="mt-14">
            <h2 class="font-cairo font-bold text-2xl mb-5 text-center">{{ __('site.faq.title') }}</h2>
            <div class="mx-auto max-w-3xl space-y-3" x-data="{ open: null }">
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
