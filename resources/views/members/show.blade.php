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
        {{-- Cover --}}
        @if ($user->coverUrl())
            <div class="h-40 bg-cover bg-center sm:h-52" style="background-image: url('{{ $user->coverUrl() }}');"></div>
        @else
            <div class="h-36 sm:h-44" style="background: radial-gradient(circle at 25% 15%, rgba(201,169,97,.30), transparent 55%), linear-gradient(120deg, #006C35, #00472a);"></div>
        @endif

        {{-- Identity (centred) --}}
        <div class="px-6 pb-7 text-center">
            <div class="-mt-16 flex justify-center">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }}"
                         class="h-28 w-28 rounded-2xl object-cover ring-4 ring-white shadow-xl">
                @else
                    <span class="flex h-28 w-28 items-center justify-center rounded-2xl bg-saudi-green text-white text-4xl font-black ring-4 ring-white shadow-xl">
                        {{ mb_substr($user->displayName(), 0, 1) }}
                    </span>
                @endif
            </div>

            <h1 class="mt-4 inline-flex items-center justify-center gap-2 font-cairo text-2xl font-black text-luxury-black">
                {{ $user->displayName() }}
                @if ($user->memberTier()->hasBadge())
                    <i class="{{ $user->memberTier()->icon() }} text-xl" style="color: {{ $user->memberTier()->color() }};"
                       title="{{ $user->memberTier()->label() }}"></i>
                @endif
            </h1>
            <p class="mt-0.5 text-sm font-semibold text-saudi-green">{{ '@'.$user->username }}</p>

            @if ($user->bio)
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-gray-600">{{ $user->bio }}</p>
            @endif

            @if ($user->website || $user->twitter || $user->github)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    @if ($user->website)
                        <a href="{{ $user->website }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-100 transition hover:text-saudi-green hover:ring-saudi-green/30">
                            <i class="fa-solid fa-globe"></i> <span dir="ltr">{{ preg_replace('#^https?://#', '', rtrim($user->website, '/')) }}</span>
                        </a>
                    @endif
                    @if ($user->twitter)
                        <a href="https://x.com/{{ $user->twitter }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-100 transition hover:text-saudi-green hover:ring-saudi-green/30">
                            <i class="fa-brands fa-x-twitter"></i> <span dir="ltr">{{ $user->twitter }}</span>
                        </a>
                    @endif
                    @if ($user->github)
                        <a href="https://github.com/{{ $user->github }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-gray-100 transition hover:text-saudi-green hover:ring-saudi-green/30">
                            <i class="fa-brands fa-github"></i> <span dir="ltr">{{ $user->github }}</span>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Stats (hidden when files are private) --}}
            @if ($showFiles)
            <div class="mx-auto mt-6 grid max-w-md grid-cols-3 gap-3">
                @foreach ([
                    ['v' => $stats['files'],     'l' => __('profile.public.files'),     'i' => 'fa-folder',     'c' => '#006C35'],
                    ['v' => $stats['downloads'], 'l' => __('profile.public.downloads'), 'i' => 'fa-arrow-down', 'c' => '#3b82f6'],
                    ['v' => $stats['views'],     'l' => __('profile.public.views'),     'i' => 'fa-eye',        'c' => '#8b5cf6'],
                ] as $s)
                    <div class="rounded-2xl bg-gray-50 py-3.5 ring-1 ring-gray-100">
                        <div class="flex items-center justify-center gap-1.5">
                            <i class="fa-solid {{ $s['i'] }} text-xs" style="color: {{ $s['c'] }};"></i>
                            <span class="text-xl font-black text-luxury-black" dir="ltr">{{ number_format($s['v']) }}</span>
                        </div>
                        <div class="mt-0.5 text-xs text-gray-400">{{ $s['l'] }}</div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Share --}}
            @php $shareUrl = url()->current(); @endphp
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2 border-t border-gray-100 pt-5">
                <span class="text-xs font-semibold text-gray-400">{{ __('profile.public.share') }}</span>
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $shareUrl }}').then(() => { const s = this.querySelector('span'); const o = s.textContent; s.textContent = '{{ __('profile.public.copied') }}'; setTimeout(() => s.textContent = o, 1500); })"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-200">
                    <i class="fa-solid fa-link"></i> <span>{{ __('profile.public.copy') }}</span>
                </button>
                <a href="https://wa.me/?text={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="WhatsApp" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:opacity-90" style="background:#25D366;"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="Telegram" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-white transition hover:opacity-90" style="background:#229ED9;"><i class="fa-brands fa-telegram"></i></a>
                <a href="https://x.com/intent/tweet?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" title="X" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-black text-white transition hover:opacity-90"><i class="fa-brands fa-x-twitter"></i></a>
            </div>
        </div>
    </div>

    {{-- ===== Shared files ===== --}}
    @if (! $showFiles)
        {{-- Visitor view when the member keeps their files private --}}
        <div class="card-luxury mt-8 p-10 text-center">
            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-2xl text-gray-400">
                <i class="fa-solid fa-lock"></i>
            </span>
            <h2 class="font-cairo text-lg font-bold text-luxury-black">{{ __('profile.public.private_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('profile.public.private_body') }}</p>
        </div>
    @else
        @if ($isOwner && ! $user->show_files_publicly)
            <div class="mt-8 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-lock mt-0.5"></i>
                <span>{!! __('profile.public.owner_private_note', ['link' => '<a href="'.route('filament.member.auth.profile').'" class="font-bold underline">'.__('profile.public.profile_settings').'</a>']) !!}</span>
            </div>
        @endif

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
    @endif

</div>
@endsection
