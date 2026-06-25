@props([
    'url' => null,
    'title' => '',
    'variant' => 'card',   // card (boxed) | light (compact row for dark backgrounds)
    'heading' => null,
])

@php
    $shareUrl = $url ?: url()->current();
    $text = trim((string) $title);
    $eu = rawurlencode($shareUrl);
    $et = rawurlencode($text);

    $networks = [
        ['label' => 'واتساب',   'icon' => 'fa-brands fa-whatsapp',   'color' => '#25D366', 'href' => "https://wa.me/?text={$et}%20{$eu}"],
        ['label' => 'X',        'icon' => 'fa-brands fa-x-twitter',  'color' => '#000000', 'href' => "https://twitter.com/intent/tweet?text={$et}&url={$eu}"],
        ['label' => 'فيسبوك',   'icon' => 'fa-brands fa-facebook-f', 'color' => '#1877F2', 'href' => "https://www.facebook.com/sharer/sharer.php?u={$eu}"],
        ['label' => 'تيليجرام', 'icon' => 'fa-brands fa-telegram',   'color' => '#229ED9', 'href' => "https://t.me/share/url?url={$eu}&text={$et}"],
        ['label' => 'لينكدإن',  'icon' => 'fa-brands fa-linkedin-in','color' => '#0A66C2', 'href' => "https://www.linkedin.com/sharing/share-offsite/?url={$eu}"],
    ];
@endphp

@once
    @push('scripts')
        <script>
            function fnoonShare(url, text) {
                return {
                    url: url, text: text, copied: false,
                    canNative: !!(navigator.share),
                    _done() { this.copied = true; setTimeout(() => this.copied = false, 1800); },
                    copy() {
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(this.url).then(() => this._done()).catch(() => this._legacy());
                        } else { this._legacy(); }
                    },
                    _legacy() {
                        const t = document.createElement('textarea');
                        t.value = this.url; t.style.position = 'fixed'; t.style.opacity = '0';
                        document.body.appendChild(t); t.focus(); t.select();
                        try { document.execCommand('copy'); } catch (e) {}
                        t.remove(); this._done();
                    },
                    native() {
                        if (navigator.share) { navigator.share({ title: this.text, url: this.url }).catch(() => {}); }
                    },
                };
            }
        </script>
    @endpush
@endonce

@if ($variant === 'light')
    {{-- Compact row for dark hero backgrounds --}}
    <div x-data="fnoonShare(@js($shareUrl), @js($text))" class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold text-white/70"><i class="fa-solid fa-share-nodes"></i> {{ $heading ?? __('site.share.label') }}</span>
        @foreach ($networks as $n)
            <a href="{{ $n['href'] }}" target="_blank" rel="noopener nofollow"
               class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white transition hover:-translate-y-0.5 hover:bg-white/25"
               aria-label="{{ $n['label'] }}" title="{{ $n['label'] }}"><i class="{{ $n['icon'] }}"></i></a>
        @endforeach
        <button type="button" @click="native()" x-show="canNative" x-cloak
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white transition hover:-translate-y-0.5 hover:bg-white/25"
                aria-label="{{ __('site.share.more') }}" title="{{ __('site.share.more') }}"><i class="fa-solid fa-ellipsis"></i></button>
        <button type="button" @click="copy()"
                class="inline-flex h-9 items-center gap-1.5 rounded-full bg-white/15 px-3 text-xs font-bold text-white transition hover:bg-white/25">
            <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-link'"></i>
            <span x-text="copied ? @js(__('site.share.copied')) : @js(__('site.share.copy'))"></span>
        </button>
    </div>
@else
    {{-- Boxed card for sidebars / articles --}}
    <div x-data="fnoonShare(@js($shareUrl), @js($text))" {{ $attributes->merge(['class' => 'card-luxury p-5']) }}>
        <h3 class="mb-3 font-cairo text-base font-black text-luxury-black">
            <i class="fa-solid fa-share-nodes text-saudi-green"></i> {{ $heading ?? __('site.share.title') }}
        </h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($networks as $n)
                <a href="{{ $n['href'] }}" target="_blank" rel="noopener nofollow"
                   class="inline-flex h-11 w-11 items-center justify-center rounded-full text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                   style="background: {{ $n['color'] }}" aria-label="{{ $n['label'] }}" title="{{ $n['label'] }}"><i class="{{ $n['icon'] }} text-lg"></i></a>
            @endforeach
            <button type="button" @click="native()" x-show="canNative" x-cloak
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-gray-700 text-white shadow-sm transition hover:-translate-y-0.5"
                    aria-label="{{ __('site.share.more') }}" title="{{ __('site.share.more') }}"><i class="fa-solid fa-ellipsis text-lg"></i></button>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <input type="text" readonly :value="url" dir="ltr" @focus="$event.target.select()"
                   class="min-w-0 flex-1 rounded-xl border-gray-200 bg-gray-50 text-xs text-gray-600">
            <button type="button" @click="copy()" class="btn-primary shrink-0 text-sm">
                <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                <span x-text="copied ? @js(__('site.share.copied')) : @js(__('site.share.copy'))"></span>
            </button>
        </div>
    </div>
@endif
