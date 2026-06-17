{{-- Generic, data-driven lab renderer. Used for any lab created from the admin
     that has no hand-built partial. Each lab item is a "block" chosen by data.block. --}}
@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Storage;
    $items = $lab->activeItems ?? collect();
    $hasCode = $items->contains(fn ($it) => $it->d('block') === 'code');
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    @forelse ($items as $i => $it)
        @php($block = $it->d('block', 'text'))
        <div class="card-luxury p-5 md:p-6">
            @if ($it->title)
                <h2 class="font-cairo font-bold text-lg text-luxury-black mb-3 flex items-center gap-2">
                    <span class="text-saudi-green">{{ $i + 1 }}.</span> {{ $it->title }}
                </h2>
            @endif

            @switch($block)
                @case('embed')
                    <div class="rounded-xl overflow-hidden border border-royal-gold/20 bg-black/5">
                        <iframe src="{{ $it->d('url') }}"
                                class="w-full block"
                                style="height: {{ max(180, (int) $it->d('height', 480)) }}px"
                                loading="lazy" allowfullscreen referrerpolicy="no-referrer"></iframe>
                    </div>
                    @break

                @case('code')
                    <div dir="ltr" x-data="{ c: false }">
                        <div class="flex justify-end mb-1">
                            <button type="button"
                                    @click="window.fnoonCopy($refs.cb{{ $i }}.innerText); c = true; setTimeout(() => c = false, 1500)"
                                    class="px-3 py-1 rounded-lg text-sm font-bold text-saudi-green hover:bg-saudi-green/10">
                                <i class="fa-solid" :class="c ? 'fa-check' : 'fa-copy'"></i>
                                <span x-text="c ? '{{ __('learn.snippets.copied') }}' : '{{ __('learn.snippets.copy') }}'"></span>
                            </button>
                        </div>
                        <pre class="!m-0 rounded-xl overflow-hidden"><code x-ref="cb{{ $i }}" class="language-{{ $it->d('lang', 'plaintext') }}">{{ $it->d('code', '') }}</code></pre>
                    </div>
                    @break

                @case('image')
                    @php($img = $it->d('image'))
                    @if ($img)
                        <figure class="m-0">
                            <img src="{{ Str::startsWith($img, ['http://', 'https://']) ? $img : Storage::disk('public')->url($img) }}"
                                 alt="{{ $it->d('caption', $it->title) }}" class="rounded-xl w-full" loading="lazy">
                            @if ($it->d('caption'))
                                <figcaption class="text-sm text-gray-500 mt-2 text-center">{{ $it->d('caption') }}</figcaption>
                            @endif
                        </figure>
                    @endif
                    @break

                @case('steps')
                    <ol class="space-y-3 list-none p-0 m-0">
                        @foreach (($it->d('steps') ?? []) as $si => $st)
                            <li class="flex gap-3">
                                <span class="h-7 w-7 shrink-0 rounded-full bg-saudi-green/10 text-saudi-green font-bold inline-flex items-center justify-center text-sm">{{ $si + 1 }}</span>
                                <div>
                                    <div class="font-semibold text-luxury-black">{{ data_get($st, 'label') }}</div>
                                    @if (data_get($st, 'text'))
                                        <p class="text-gray-600 text-sm mt-0.5 whitespace-pre-line">{{ data_get($st, 'text') }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    @break

                @case('link')
                    <a href="{{ $it->d('href', '#') }}" target="_blank" rel="noopener" class="btn-primary">
                        <i class="fa-solid fa-arrow-up-right-from-square rtl:-scale-x-100"></i>
                        {{ $it->d('label') ?: $it->title }}
                    </a>
                    @break

                @default
                    <div class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $it->d('body', $it->description) }}</div>
            @endswitch
        </div>
    @empty
        <div class="text-center text-gray-400 py-16">
            <i class="fa-solid fa-flask text-4xl mb-3"></i>
            <p>{{ __('learn.lab.empty') }}</p>
        </div>
    @endforelse
</div>

@if ($hasCode)
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
        <style>.card-luxury pre code{display:block;padding:1rem;font-size:.82rem;line-height:1.65;background:#0d1117;color:#e6edf3}</style>
    @endpush
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
        <script>document.addEventListener('DOMContentLoaded', () => { if (window.hljs) hljs.highlightAll(); });</script>
    @endpush
@endif
