@extends('layouts.app')

@section('title', $asset->original_name)

@section('content')
@php
    $fmt = function ($b) {
        $b = (int) $b; if ($b <= 0) return '0 B';
        $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
@endphp

<div class="max-w-5xl mx-auto px-4 py-10">

    @if ($expired)
        {{-- ===== Expired ===== --}}
        <div class="card-luxury p-10 text-center">
            <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                <i class="fa-solid fa-link-slash text-2xl"></i>
            </span>
            <h1 class="font-cairo text-xl font-bold">{{ __('asset.expired') }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ __('asset.expired_body') }}</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6 inline-flex">{{ __('site.nav.home') }}</a>
        </div>

    @elseif ($locked)
        {{-- ===== Password gate ===== --}}
        <div class="card-luxury mx-auto max-w-md p-8 text-center">
            <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-saudi-green/10 text-saudi-green">
                <i class="fa-solid fa-lock text-2xl"></i>
            </span>
            <h1 class="font-cairo text-xl font-bold">{{ __('asset.protected') }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ __('asset.password_prompt') }}</p>
            <form method="POST" action="{{ route('assets.unlock', $asset) }}" class="mt-6 space-y-3">
                @csrf
                <input type="password" name="password" required autofocus
                       placeholder="{{ __('asset.password') }}"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-center focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">
                @error('password')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                <button class="btn-primary w-full justify-center"><i class="fa-solid fa-unlock"></i> {{ __('asset.unlock') }}</button>
            </form>
        </div>

    @else
        {{-- ===== Asset ===== --}}
        <div x-data="shareKit('{{ $asset->pageUrl() }}')" class="grid gap-8 lg:grid-cols-5">

            {{-- Preview --}}
            <div class="lg:col-span-3 space-y-4">
                <div class="card-luxury overflow-hidden p-0">
                    @if ($asset->isImage())
                        <a href="{{ $asset->directUrl() }}" target="_blank" class="block bg-[repeating-conic-gradient(#f3f4f6_0%_25%,#fff_0%_50%)] bg-[length:24px_24px]">
                            <img src="{{ $asset->directUrl() }}" alt="{{ $asset->original_name }}" class="mx-auto max-h-[60vh] w-auto">
                        </a>
                    @elseif ($asset->isPdf())
                        <iframe src="{{ $asset->directUrl() }}" class="h-[70vh] w-full" title="{{ $asset->original_name }}"></iframe>
                    @else
                        @php
                            $ext = strtolower(pathinfo($asset->original_name, PATHINFO_EXTENSION));
                            $icon = match (true) {
                                in_array($ext, ['zip','rar','7z','tar','gz','tgz','bz2','xz']) => 'fa-file-zipper text-amber-500',
                                in_array($ext, ['exe','msi','dmg','pkg','deb','rpm','appimage']) => 'fa-window-maximize text-sky-500',
                                in_array($ext, ['apk','aab','ipa']) => 'fa-mobile-screen text-green-500',
                                in_array($ext, ['iso','img','bin']) => 'fa-compact-disc text-indigo-500',
                                default => 'fa-file text-gray-400',
                            };
                        @endphp
                        <div class="flex flex-col items-center justify-center gap-4 px-6 py-16">
                            <i class="fa-solid {{ $icon }} text-7xl"></i>
                            <div class="text-center">
                                <div class="font-cairo text-lg font-bold break-all">{{ $asset->original_name }}</div>
                                <div class="mt-1 text-sm text-gray-400" dir="ltr">{{ $fmt($asset->size_bytes) }} · {{ strtoupper($ext) }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- meta strip --}}
                <div class="card-luxury grid grid-cols-2 gap-px overflow-hidden bg-gray-100 p-0 sm:grid-cols-4">
                    <div class="bg-white px-4 py-3 text-center">
                        <div class="font-bold" dir="ltr">{{ $fmt($asset->size_bytes) }}</div>
                        <div class="text-[11px] text-gray-400">{{ __('asset.size') }}</div>
                    </div>
                    <div class="bg-white px-4 py-3 text-center">
                        <div class="font-bold" dir="ltr">{{ number_format($asset->downloads_count) }}</div>
                        <div class="text-[11px] text-gray-400">{{ __('asset.downloads') }}</div>
                    </div>
                    <div class="bg-white px-4 py-3 text-center">
                        <div class="font-bold" dir="ltr">{{ number_format($asset->views_count) }}</div>
                        <div class="text-[11px] text-gray-400">{{ __('asset.views') }}</div>
                    </div>
                    <div class="bg-white px-4 py-3 text-center">
                        <div class="font-bold" dir="ltr">{{ $asset->isImage() && $asset->width ? $asset->width.'×'.$asset->height : ($asset->pages ? $asset->pages : strtoupper(pathinfo($asset->original_name, PATHINFO_EXTENSION))) }}</div>
                        <div class="text-[11px] text-gray-400">{{ $asset->isImage() ? __('asset.preview') : ($asset->pages ? __('asset.pages') : __('asset.type')) }}</div>
                    </div>
                </div>

                @if ($asset->checksum_sha256)
                    <div class="card-luxury flex items-center gap-2 p-3 text-xs">
                        <span class="font-semibold text-gray-500">{{ __('asset.checksum') }}</span>
                        <code class="flex-1 truncate font-mono text-gray-600" dir="ltr">{{ $asset->checksum_sha256 }}</code>
                        <button @click="copy('{{ $asset->checksum_sha256 }}', 'sha')" class="text-saudi-green hover:opacity-70">
                            <i class="fa-solid" :class="done==='sha' ? 'fa-check' : 'fa-copy'"></i>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Actions + share kit --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="card-luxury p-6">
                    <h1 class="font-cairo text-lg font-bold break-all">{{ $asset->original_name }}</h1>
                    <a href="{{ route('assets.download', $asset) }}" class="btn-primary mt-4 w-full justify-center text-lg">
                        <i class="fa-solid fa-download"></i> {{ __('asset.download') }}
                    </a>

                    {{-- QR --}}
                    <div class="mt-5 flex items-center gap-4 rounded-xl bg-gray-50 p-4">
                        <div id="asset-qr" class="rounded-lg bg-white p-1.5 shadow-sm"></div>
                        <div class="text-xs text-gray-500">
                            <div class="font-semibold text-gray-700">{{ __('asset.qr') }}</div>
                            <div class="mt-1 break-all font-mono text-[10px]" dir="ltr">{{ $asset->pageUrl() }}</div>
                        </div>
                    </div>
                </div>

                {{-- Share kit --}}
                <div class="card-luxury p-6">
                    <h2 class="mb-4 flex items-center gap-2 font-cairo text-base font-bold">
                        <i class="fa-solid fa-share-nodes text-saudi-green"></i> {{ __('asset.share_kit') }}
                    </h2>
                    <div class="space-y-3">
                        @foreach ($kit as $block)
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">{{ $block['label'] }}</span>
                                    <button @click="copyEl($refs['c{{ $loop->index }}'], '{{ $loop->index }}')"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-saudi-green hover:opacity-70">
                                        <i class="fa-solid" :class="done==='{{ $loop->index }}' ? 'fa-check' : 'fa-copy'"></i>
                                        <span x-text="done==='{{ $loop->index }}' ? '{{ __('asset.copied') }}' : '{{ __('asset.copy') }}'"></span>
                                    </button>
                                </div>
                                <textarea x-ref="c{{ $loop->index }}" readonly rows="{{ \Illuminate\Support\Str::contains($block['code'], '<') ? 2 : 1 }}"
                                          class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700 focus:border-saudi-green focus:outline-none"
                                          dir="ltr" onclick="this.select()">{{ $block['code'] }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if (! $expired && ! $locked)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            function shareKit(pageUrl) {
                return {
                    done: null,
                    flash(key) { this.done = key; setTimeout(() => { if (this.done === key) this.done = null; }, 1600); },
                    copy(text, key) { window.fnoonCopy(text).then(() => this.flash(key)); },
                    copyEl(el, key) { if (el) this.copy(el.value, key); },
                };
            }
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('asset-qr');
                if (el && window.QRCode) {
                    new QRCode(el, { text: @json($asset->pageUrl()), width: 84, height: 84, colorDark: '#006C35' });
                }
            });
        </script>
    @endpush
@endif
