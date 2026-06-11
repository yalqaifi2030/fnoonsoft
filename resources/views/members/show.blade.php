@extends('layouts.app')

@section('title', $user->displayName().' (@'.$user->username.')')

@section('og_title', $user->displayName().' (@'.$user->username.')')
@section('og_description', $user->bio ?: __('profile.public.og_desc', ['name' => $user->displayName()]))
@section('og_image', $user->coverUrl() ?: ($user->avatarUrl() ?: ''))

@section('content')
@php
    $fmt = function ($b) {
        $b = (int) $b; if ($b <= 0) return '0 B';
        $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
    $fileIcon = function ($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['zip','rar','7z','tar','gz','tgz','bz2','xz']) => 'fa-file-zipper text-amber-500',
            in_array($ext, ['exe','msi','dmg','pkg','deb','rpm','appimage']) => 'fa-window-maximize text-sky-500',
            in_array($ext, ['apk','aab','ipa']) => 'fa-mobile-screen text-green-500',
            in_array($ext, ['iso','img','bin']) => 'fa-compact-disc text-indigo-500',
            in_array($ext, ['pdf']) => 'fa-file-pdf text-red-500',
            in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) => 'fa-file-image text-blue-500',
            default => 'fa-file text-gray-400',
        };
    };
@endphp

<div class="max-w-5xl mx-auto px-4 py-10">

    {{-- ===== Profile header ===== --}}
    <div class="card-luxury overflow-hidden p-0">
        @if ($user->coverUrl())
            <div class="h-40 bg-cover bg-center sm:h-48" style="background-image: url('{{ $user->coverUrl() }}');"></div>
        @else
            <div class="h-32" style="background: linear-gradient(120deg, #006C35, #00472a);"></div>
        @endif
        <div class="px-6 pb-6 -mt-12">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="shrink-0">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }}"
                             class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white shadow-lg">
                    @else
                        <span class="flex h-24 w-24 items-center justify-center rounded-2xl bg-saudi-green text-white text-3xl font-black ring-4 ring-white shadow-lg">
                            {{ mb_substr($user->displayName(), 0, 1) }}
                        </span>
                    @endif
                </div>
                <div class="min-w-0 flex-1 sm:pb-1">
                    <h1 class="inline-flex items-center gap-2 font-cairo text-2xl font-black text-luxury-black">
                        {{ $user->displayName() }}
                        @if ($user->memberTier()->hasBadge())
                            <i class="{{ $user->memberTier()->icon() }} text-xl" style="color: {{ $user->memberTier()->color() }};"
                               title="{{ $user->memberTier()->label() }}"></i>
                        @endif
                    </h1>
                    <p class="text-sm font-semibold text-saudi-green" dir="ltr">{{ '@'.$user->username }}</p>
                </div>
            </div>

            @if ($user->bio)
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-gray-600">{{ $user->bio }}</p>
            @endif

            @if ($user->website || $user->twitter || $user->github)
                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm" dir="ltr">
                    @if ($user->website)
                        <a href="{{ $user->website }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-saudi-green">
                            <i class="fa-solid fa-globe"></i> {{ preg_replace('#^https?://#', '', rtrim($user->website, '/')) }}
                        </a>
                    @endif
                    @if ($user->twitter)
                        <a href="https://x.com/{{ $user->twitter }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-saudi-green">
                            <i class="fa-brands fa-x-twitter"></i> {{ $user->twitter }}
                        </a>
                    @endif
                    @if ($user->github)
                        <a href="https://github.com/{{ $user->github }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-saudi-green">
                            <i class="fa-brands fa-github"></i> {{ $user->github }}
                        </a>
                    @endif
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-6 border-t border-gray-100 pt-4">
                <div><span class="text-xl font-black text-luxury-black" dir="ltr">{{ number_format($stats['files']) }}</span> <span class="text-xs text-gray-400">{{ __('profile.public.files') }}</span></div>
                <div><span class="text-xl font-black text-luxury-black" dir="ltr">{{ number_format($stats['downloads']) }}</span> <span class="text-xs text-gray-400">{{ __('profile.public.downloads') }}</span></div>
                <div><span class="text-xl font-black text-luxury-black" dir="ltr">{{ number_format($stats['views']) }}</span> <span class="text-xs text-gray-400">{{ __('profile.public.views') }}</span></div>
            </div>

            {{-- Share --}}
            @php $shareUrl = url()->current(); @endphp
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                <span class="text-xs font-semibold text-gray-400">{{ __('profile.public.share') }}</span>
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $shareUrl }}').then(() => { const s = this.querySelector('span'); const o = s.textContent; s.textContent = '{{ __('profile.public.copied') }}'; setTimeout(() => s.textContent = o, 1500); })"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-200">
                    <i class="fa-solid fa-link"></i> <span>{{ __('profile.public.copy') }}</span>
                </button>
                <a href="https://wa.me/?text={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="WhatsApp" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-90" style="background:#25D366;"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="Telegram" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-90" style="background:#229ED9;"><i class="fa-brands fa-telegram"></i></a>
                <a href="https://x.com/intent/tweet?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="X" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-black text-white transition hover:opacity-90"><i class="fa-brands fa-x-twitter"></i></a>
            </div>
        </div>
    </div>

    {{-- ===== Shared files ===== --}}
    <h2 class="mb-4 mt-8 font-cairo text-lg font-bold">{{ __('profile.public.shared_files') }}</h2>

    @if ($assets->isEmpty())
        <div class="card-luxury p-10 text-center text-gray-400">
            <i class="fa-regular fa-folder-open mb-3 text-4xl"></i>
            <p>{{ __('profile.public.no_files') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($assets as $a)
                <a href="{{ route('assets.show', $a) }}" class="card-luxury group flex items-center gap-3 p-4 transition hover:shadow-lg">
                    @if ($a->isImage() && $a->thumbUrl())
                        <img src="{{ $a->thumbUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                    @else
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-gray-50">
                            <i class="fa-solid {{ $fileIcon($a->original_name) }} text-2xl"></i>
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-luxury-black group-hover:text-saudi-green">{{ $a->original_name }}</div>
                        <div class="mt-0.5 text-xs text-gray-400" dir="ltr">{{ $fmt($a->size_bytes) }} · <i class="fa-solid fa-arrow-down"></i> {{ number_format($a->downloads_count) }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-left text-gray-300 group-hover:text-saudi-green"></i>
                </a>
            @endforeach
        </div>
    @endif

</div>
@endsection
