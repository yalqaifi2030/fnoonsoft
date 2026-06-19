@extends('layouts.app')

@section('title', __('report.err500_title'))

@section('content')
<div class="mx-auto max-w-xl px-4 py-20 text-center">
    <div class="card-luxury p-10">
        <span class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
            <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
        </span>
        <h1 class="font-cairo text-2xl font-black">{{ __('report.err500_title') }}</h1>
        <p class="mt-2 text-gray-500">{{ __('report.err500_body') }}</p>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">
                <i class="fa-solid fa-house"></i> {{ __('report.err_home') }}
            </a>
            <button type="button"
                    onclick="window.fnoonReport({ source: 'error', error: 'HTTP 500' })"
                    class="inline-flex items-center gap-2 rounded-xl border border-saudi-green/40 px-5 py-2.5 text-sm font-bold text-saudi-green transition hover:bg-saudi-green/5">
                <i class="fa-solid fa-bug"></i> {{ __('report.button') }}
            </button>
        </div>
    </div>
</div>
@endsection
