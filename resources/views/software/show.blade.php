@extends('layouts.app')

@section('title', $software->name)
@section('meta_description', $software->short_description)
@section('og_title', $software->name)
@section('og_description', $software->short_description)
@section('og_image', $software->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) : '')

@push('jsonld')
    <script type="application/ld+json">{!! json_encode($software->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
@php($shots = $software->screenshots->map(fn ($s) => ['src' => \Illuminate\Support\Facades\Storage::disk('public')->url($s->path), 'cap' => (string) $s->caption])->values())
<div class="max-w-7xl mx-auto px-4 py-8"
     x-data="{
        lb:false, i:0,
        imgs: @js($shots),
        open(idx){ this.i = idx; this.lb = true },
        next(){ if (this.imgs.length) this.i = (this.i + 1) % this.imgs.length },
        prev(){ if (this.imgs.length) this.i = (this.i - 1 + this.imgs.length) % this.imgs.length },
        get cur(){ return this.imgs[this.i] || { src:'', cap:'' } }
     }">
    @php($primary = $software->downloadLinks->first())
    @php($totalSize = (int) $software->downloadLinks->sum('size_bytes'))

    {{-- Record this download to the visitor's browser history (localStorage) when
         any download trigger is clicked. Members also get an account-based history. --}}
    @php($dlData = [
        'slug' => $software->slug,
        'name' => (string) $software->name,
        'icon' => $software->icon ? \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) : null,
        'dev' => optional($software->developer)->name,
    ])
    @push('scripts')
        <script>
            (function () {
                const dl = @json($dlData);
                function record() {
                    try {
                        const k = 'fnoon_downloads';
                        let arr = JSON.parse(localStorage.getItem(k) || '[]');
                        if (!Array.isArray(arr)) arr = [];
                        arr = arr.filter(x => x && x.slug !== dl.slug);
                        arr.unshift(Object.assign({}, dl, { at: Date.now() }));
                        localStorage.setItem(k, JSON.stringify(arr.slice(0, 60)));
                    } catch (e) {}
                }
                document.addEventListener('click', function (e) {
                    if (e.target.closest('a[href*="/download/"], [data-dl-all]')) record();
                }, true);
            })();
        </script>
    @endpush
    @php($tabs = array_values(array_filter([
        ['id' => 'about', 'label' => __('site.about')],
        $software->hasVideo() ? ['id' => 'video', 'label' => __('software.section.video')] : null,
        ! empty($software->features) ? ['id' => 'features', 'label' => __('software.section.features')] : null,
        $software->screenshots->isNotEmpty() ? ['id' => 'screenshots', 'label' => __('site.screenshots')] : null,
        $software->activeBeforeAfterSlides->isNotEmpty() ? ['id' => 'before_after', 'label' => __('site.before_after.title')] : null,
        $software->fileFormats->where('is_active', true)->isNotEmpty() ? ['id' => 'formats', 'label' => __('formats.section')] : null,
        $software->has3dModel() ? ['id' => 'model3d', 'label' => __('software.section.model')] : null,
        $software->hasCode() ? ['id' => 'code', 'label' => __('software.section.code')] : null,
        $software->requirements->isNotEmpty() ? ['id' => 'requirements', 'label' => __('site.requirements')] : null,
        ['id' => 'reviews', 'label' => __('site.feedback')],
    ])))

    {{-- Breadcrumb --}}
    <nav class="mb-5 flex items-center gap-2 text-xs text-gray-400" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="transition hover:text-saudi-green">{{ __('site.nav.home') }}</a>
        <span class="opacity-50">/</span>
        <a href="{{ route('browse', ['type' => $software->content_type->value]) }}" class="transition hover:text-saudi-green">{{ $software->content_type->label() }}</a>
        <span class="opacity-50">/</span>
        <span class="text-gray-600 truncate">{{ $software->name }}</span>
    </nav>

    {{-- Dismissible notice / announcement (per-software, managed from admin) --}}
    @if ($software->notice_enabled && filled($software->notice_text))
        @php($ntype = $software->notice_type ?: 'info')
        @php($nmap = [
            'info'    => ['bg' => '#eff6ff', 'bd' => '#bfdbfe', 'tx' => '#1e40af', 'ic' => 'fa-circle-info'],
            'success' => ['bg' => '#ecfdf5', 'bd' => '#a7f3d0', 'tx' => '#065f46', 'ic' => 'fa-circle-check'],
            'warning' => ['bg' => '#fffbeb', 'bd' => '#fde68a', 'tx' => '#92400e', 'ic' => 'fa-triangle-exclamation'],
            'promo'   => ['bg' => '#faf5ff', 'bd' => '#e9d5ff', 'tx' => '#6b21a8', 'ic' => 'fa-bullhorn'],
        ])
        @php($ns = $nmap[$ntype] ?? $nmap['info'])
        @php($nkey = 'fn_notice_'.$software->id.'_'.substr(md5((string) $software->notice_text), 0, 8))
        <div x-data="{ show: localStorage.getItem('{{ $nkey }}') !== '1' }" x-show="show" x-cloak
             class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3"
             style="display:none; background: {{ $ns['bg'] }}; border-color: {{ $ns['bd'] }}; color: {{ $ns['tx'] }}">
            <i class="fa-solid {{ $ns['ic'] }} mt-0.5 text-lg"></i>
            <div class="min-w-0 flex-1 text-sm leading-relaxed">
                <span class="whitespace-pre-line">{{ $software->notice_text }}</span>
                @if ($software->notice_url)
                    <a href="{{ $software->notice_url }}" target="_blank" rel="noopener" class="ms-1 font-bold underline">{{ __('software.notice.learn_more') }}</a>
                @endif
            </div>
            <button type="button" @click="show = false; localStorage.setItem('{{ $nkey }}', '1')"
                    class="shrink-0 opacity-60 transition hover:opacity-100" aria-label="{{ __('software.notice.close') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- Hero --}}
    <div class="card-luxury relative overflow-hidden p-0">
        <div class="absolute inset-0 bg-gradient-to-br from-saudi-green to-[#00582b]"></div>
        <div class="absolute -end-12 -top-12 h-48 w-48 rounded-full bg-white/5"></div>
        <div class="absolute -start-10 bottom-0 h-44 w-44 rounded-full bg-white/5"></div>

        <div class="relative p-6 text-white sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                {{-- Icon --}}
                @if ($software->icon)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($software->icon) }}"
                         width="112" height="112" alt="{{ $software->name }}"
                         class="h-24 w-24 flex-shrink-0 rounded-3xl object-cover shadow-xl ring-4 ring-white/20 sm:h-28 sm:w-28">
                @else
                    <span class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-3xl bg-white/15 ring-4 ring-white/20 sm:h-28 sm:w-28">
                        <i class="{{ $software->content_type->icon() }} text-5xl"></i>
                    </span>
                @endif

                {{-- Identity --}}
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur-sm">{{ $software->content_type->label() }}</span>
                        @if ($software->is_editor_choice)
                            <span class="rounded-full bg-royal-gold px-2.5 py-0.5 text-xs font-bold text-[#3a2c00]"><i class="fa-solid fa-award"></i> {{ __('site.sections.editor_choice') }}</span>
                        @endif
                        @if ($software->is_malware_free)
                            <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur-sm"><i class="fa-solid fa-shield-halved"></i> {{ __('site.card.malware_free') }}</span>
                        @endif
                        @if ($software->virusScanClean())
                            <span class="rounded-full bg-green-500/25 px-2.5 py-0.5 text-xs font-bold backdrop-blur-sm" title="VirusTotal"><i class="fa-solid fa-shield-virus"></i> {{ __('site.card.scanned') }}</span>
                        @endif
                    </div>
                    @php($titleLen = mb_strlen((string) $software->name))
                    @php($titleSize = $titleLen > 60 ? 'clamp(1.15rem, 3.6vw, 1.7rem)' : ($titleLen > 35 ? 'clamp(1.4rem, 4.2vw, 2rem)' : 'clamp(1.7rem, 5vw, 2.25rem)'))
                    <h1 class="mt-3 font-cairo font-black" style="font-size: {{ $titleSize }}; line-height: 1.25; word-break: break-word;">{{ $software->name }}</h1>
                    <p class="mt-1 text-sm text-white/70">
                        {{ $software->developer?->name }}
                        @if ($software->current_version) · <span dir="ltr">v{{ $software->current_version }}</span> @endif
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="flex items-center gap-1 text-royal-gold" dir="ltr">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa-{{ $i <= round($software->rating_avg) ? 'solid' : 'regular' }} fa-star text-sm"></i>
                            @endfor
                        </span>
                        <span class="text-sm text-white/70"><span dir="ltr">{{ number_format($software->rating_avg, 1) }}</span> ({{ $software->reviews_count }})</span>
                    </div>
                </div>

            </div>

            {{-- Quick stats --}}
            <div class="mt-7 grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-4">
                <div class="bg-white/5 px-4 py-3 text-center">
                    <div class="font-cairo text-xl font-black" dir="ltr">{{ number_format($software->downloads_count) }}</div>
                    <div class="text-xs text-white/60">{{ __('site.download.downloads') }}</div>
                </div>
                <div class="bg-white/5 px-4 py-3 text-center">
                    <div class="font-cairo text-xl font-black" dir="ltr">{{ number_format($software->rating_avg, 1) }} <i class="fa-solid fa-star text-sm text-royal-gold"></i></div>
                    <div class="text-xs text-white/60">{{ __('site.reviews.title') }}</div>
                </div>
                <div class="bg-white/5 px-4 py-3 text-center">
                    <div class="font-cairo text-xl font-black" dir="ltr">{{ $software->downloadLinks->count() > 1 ? \Illuminate\Support\Number::fileSize($totalSize, precision: 2) : ($primary?->humanSize() ?? '—') }}</div>
                    <div class="text-xs text-white/60">{{ __('site.download.size') }}</div>
                </div>
                <div class="bg-white/5 px-4 py-3 text-center">
                    <div class="font-cairo text-xl font-black" dir="ltr">{{ $software->current_version ?? '—' }}</div>
                    <div class="text-xs text-white/60">{{ __('site.download.version') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky section tabs --}}
    <div class="sticky top-16 z-30 -mx-4 mb-2 mt-6 border-b border-gray-100 bg-white/95 px-4 backdrop-blur"
         x-data="{ active: '{{ $tabs[0]['id'] }}', init() {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach((e) => { if (e.isIntersecting) this.active = e.target.id });
            }, { rootMargin: '-25% 0px -65% 0px' });
            document.querySelectorAll('[data-spy]').forEach((el) => obs.observe(el));
         } }">
        <nav class="flex gap-1.5 overflow-x-auto py-3 text-sm">
            @foreach ($tabs as $tab)
                <a href="#{{ $tab['id'] }}"
                   :class="active === '{{ $tab['id'] }}' ? 'bg-saudi-green text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                   class="whitespace-nowrap rounded-full px-4 py-1.5 font-medium transition">{{ $tab['label'] }}</a>
            @endforeach
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Description (collapsible) --}}
            <div id="about" data-spy class="card-luxury p-6 scroll-mt-32" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-2 text-start focus:outline-none"
                        :aria-expanded="open">
                    <span class="font-cairo font-bold text-xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-saudi-green"></i> {{ __('site.about') }}
                    </span>
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-saudi-green/10 text-saudi-green transition-transform duration-300"
                          :class="open ? 'rotate-180' : ''">
                        <i class="fa-solid fa-chevron-down text-sm"></i>
                    </span>
                </button>
                <div x-show="open" x-collapse x-cloak class="prose max-w-none mt-4">
                    {!! $software->description !!}
                </div>
                <p x-show="!open" class="mt-2 text-sm text-gray-400">{{ __('site.read_more_hint') }}</p>
            </div>

            {{-- Explainer video --}}
            @if ($software->hasVideo())
                <div id="video" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-play text-saudi-green"></i> {{ __('software.section.video') }}
                    </h2>
                    <div class="overflow-hidden rounded-2xl bg-black aspect-video">
                        @if ($software->videoPlayerType() === 'youtube')
                            <iframe src="{{ $software->youtubeEmbedUrl() }}"
                                    class="h-full w-full" loading="lazy"
                                    title="{{ $software->name }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        @else
                            <video controls preload="metadata" class="h-full w-full" src="{{ $software->videoSrc() }}">
                            </video>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Features --}}
            @if (!empty($software->features))
                <div id="features" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-star text-royal-gold"></i> {{ __('software.section.features') }}
                    </h2>
                    <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-3">
                        @foreach ($software->features as $feature)
                            @php($text = is_array($feature) ? ($feature[app()->getLocale()] ?? ($feature['en'] ?? reset($feature))) : $feature)
                            @if ($text)
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-saudi-green/10 text-saudi-green">
                                        <i class="fa-solid fa-check text-xs"></i>
                                    </span>
                                    <span class="text-sm text-gray-700 leading-relaxed">{{ $text }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-ad placement="incontent" />

            {{-- Screenshots --}}
            @if ($software->screenshots->isNotEmpty())
                <div id="screenshots" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-images text-saudi-green"></i> {{ __('site.screenshots') }}
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach ($software->screenshots as $shot)
                            @php($src = \Illuminate\Support\Facades\Storage::disk('public')->url($shot->path))
                            <button type="button" @click="open({{ $loop->index }})"
                                    class="group relative overflow-hidden rounded-xl border border-royal-gold/10 focus:outline-none focus:ring-2 focus:ring-saudi-green/40">
                                <img src="{{ $src }}" loading="lazy" alt="{{ $shot->caption }}"
                                     class="h-40 w-full object-cover transition duration-300 group-hover:scale-105">
                                <span class="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100">
                                    <i class="fa-solid fa-magnifying-glass-plus text-lg"></i>
                                </span>
                                @if ($shot->caption)
                                    <span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-2 py-1 text-start text-[11px] text-white">{{ $shot->caption }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Before / after comparisons --}}
            @if ($software->activeBeforeAfterSlides->isNotEmpty())
                <div id="before_after" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-left-right text-saudi-green"></i> {{ __('site.before_after.title') }}
                    </h2>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        @foreach ($software->activeBeforeAfterSlides as $slide)
                            <x-before-after
                                :before="$slide->beforeUrl()"
                                :after="$slide->afterUrl()"
                                :type="$slide->media_type"
                                :before-label="$slide->before_label"
                                :after-label="$slide->after_label"
                                :caption="$slide->caption" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Supported file formats --}}
            @php($formats = $software->fileFormats->where('is_active', true))
            @if ($formats->isNotEmpty())
                <div id="formats" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-1 flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-saudi-green"></i> {{ __('formats.section') }}
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">{{ __('formats.section_hint') }}</p>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($formats as $format)
                            <a href="{{ route('formats.index') }}#{{ $format->extension }}">
                                <x-format-badge :format="$format" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3D model preview --}}
            @if ($software->has3dModel())
                @once
                    @push('styles')
                        <style>.mv-wrap:fullscreen{height:100vh !important;width:100vw;border-radius:0}.mv-wrap:-webkit-full-screen{height:100vh !important;border-radius:0}.is-loading .obj-loading{display:flex}.obj-loading{display:none}</style>
                    @endpush
                @endonce
                <div id="model3d" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-cube text-saudi-green"></i> {{ __('software.section.model') }}
                    </h2>

                @php($ctrlBtn = 'flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/25')

                @if ($software->is3dObj())
                    {{-- ===== Three.js viewer for .obj ===== --}}
                    <div class="mv-wrap is-loading relative overflow-hidden rounded-xl" data-obj-viewer data-src="{{ $software->modelGlbUrl() }}"
                         style="height:clamp(320px, 60vh, 560px); background:#f1f5f9">
                        <div class="obj-canvas" style="width:100%; height:100%"></div>
                        <div class="obj-loading pointer-events-none absolute inset-0 items-center justify-center text-gray-400">
                            <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
                        </div>
                        <div class="absolute bottom-3 end-3 flex items-center gap-0.5 rounded-full bg-black/55 p-1 text-white backdrop-blur">
                            <button type="button" data-action="rotate" class="{{ $ctrlBtn }}" title="{{ __('software.model_play') }}"><i class="fa-solid fa-pause"></i></button>
                            <button type="button" data-action="reset" class="{{ $ctrlBtn }}" title="{{ __('software.model_reset') }}"><i class="fa-solid fa-arrows-rotate"></i></button>
                            <button type="button" data-action="help" class="{{ $ctrlBtn }}" title="{{ __('software.model_controls') }}"><i class="fa-solid fa-circle-question"></i></button>
                            <button type="button" data-action="fullscreen" class="{{ $ctrlBtn }}" title="{{ __('software.model_fs') }}"><i class="fa-solid fa-expand"></i></button>
                        </div>
                        <div class="obj-help absolute bottom-14 end-3 w-56 rounded-xl bg-black/80 p-3 text-xs text-white backdrop-blur" style="display:none">
                            <div class="mb-1.5 font-bold">{{ __('software.model_controls') }}</div>
                            <ul class="space-y-1.5 text-white/80">
                                <li><i class="fa-solid fa-arrows-up-down-left-right w-4"></i> {{ __('software.model_help_rotate') }}</li>
                                <li><i class="fa-solid fa-magnifying-glass w-4"></i> {{ __('software.model_help_zoom') }}</li>
                                <li><i class="fa-solid fa-expand w-4"></i> {{ __('software.model_help_fs') }}</li>
                            </ul>
                        </div>
                    </div>
                    @once
                        @push('scripts')
                            <script type="importmap">
                            { "imports": { "three": "https://cdn.jsdelivr.net/npm/three@0.161.0/build/three.module.js", "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.161.0/examples/jsm/" } }
                            </script>
                            <script type="module">
                                import * as THREE from 'three';
                                import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
                                import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';

                                document.querySelectorAll('[data-obj-viewer]').forEach((wrap) => {
                                    const host = wrap.querySelector('.obj-canvas');
                                    const url = wrap.getAttribute('data-src');
                                    if (!host || !url) return;
                                    const w = () => host.clientWidth || 1, h = () => host.clientHeight || 1;

                                    const scene = new THREE.Scene();
                                    scene.background = new THREE.Color(0xf1f5f9);
                                    const camera = new THREE.PerspectiveCamera(45, w() / h(), 0.01, 100000);
                                    const renderer = new THREE.WebGLRenderer({ antialias: true });
                                    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                                    renderer.setSize(w(), h());
                                    host.appendChild(renderer.domElement);

                                    scene.add(new THREE.HemisphereLight(0xffffff, 0x555555, 1.0));
                                    const d1 = new THREE.DirectionalLight(0xffffff, 1.1); d1.position.set(6, 10, 8); scene.add(d1);
                                    const d2 = new THREE.DirectionalLight(0xffffff, 0.5); d2.position.set(-6, -4, -8); scene.add(d2);

                                    const controls = new OrbitControls(camera, renderer.domElement);
                                    controls.enableDamping = true; controls.autoRotate = true; controls.autoRotateSpeed = 1.4;
                                    let homePos = null;

                                    new OBJLoader().load(url, (obj) => {
                                        obj.traverse((c) => { if (c.isMesh) c.material = new THREE.MeshStandardMaterial({ color: 0xc9ced8, metalness: 0.08, roughness: 0.78 }); });
                                        const box = new THREE.Box3().setFromObject(obj);
                                        const size = box.getSize(new THREE.Vector3());
                                        const center = box.getCenter(new THREE.Vector3());
                                        obj.position.sub(center);
                                        const maxDim = Math.max(size.x, size.y, size.z) || 1;
                                        const dist = maxDim * 2.4;
                                        camera.position.set(dist * 0.5, dist * 0.35, dist);
                                        camera.near = maxDim / 200; camera.far = maxDim * 200; camera.updateProjectionMatrix();
                                        controls.target.set(0, 0, 0); controls.update();
                                        homePos = camera.position.clone();
                                        scene.add(obj);
                                        wrap.classList.remove('is-loading');
                                    }, undefined, () => { wrap.classList.remove('is-loading'); });

                                    const resize = () => { renderer.setSize(w(), h()); camera.aspect = w() / h(); camera.updateProjectionMatrix(); };
                                    window.addEventListener('resize', resize);
                                    document.addEventListener('fullscreenchange', () => setTimeout(resize, 80));
                                    (function loop() { requestAnimationFrame(loop); controls.update(); renderer.render(scene, camera); })();

                                    wrap.querySelectorAll('[data-action]').forEach((btn) => btn.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        const a = btn.getAttribute('data-action'), icon = btn.querySelector('i');
                                        if (a === 'rotate') { controls.autoRotate = !controls.autoRotate; if (icon) icon.className = 'fa-solid ' + (controls.autoRotate ? 'fa-pause' : 'fa-play'); }
                                        else if (a === 'reset') { if (homePos) { camera.position.copy(homePos); controls.target.set(0, 0, 0); controls.update(); } }
                                        else if (a === 'fullscreen') { if (!document.fullscreenElement) (wrap.requestFullscreen || function () {}).call(wrap); else document.exitFullscreen(); }
                                        else if (a === 'help') { const hp = wrap.querySelector('.obj-help'); if (hp) hp.style.display = hp.style.display === 'block' ? 'none' : 'block'; }
                                    }));
                                });
                            </script>
                        @endpush
                    @endonce
                @else
                    {{-- ===== model-viewer for .glb / .gltf ===== --}}
                    <div x-data="fnoonModel3d()" x-ref="wrap"
                         x-init="document.addEventListener('fullscreenchange', () => fs = !!document.fullscreenElement)"
                         class="mv-wrap relative overflow-hidden rounded-xl"
                         style="height:clamp(320px, 60vh, 560px); background:#f1f5f9">
                        <model-viewer x-ref="mv"
                            src="{{ $software->modelGlbUrl() }}"
                            @if ($software->modelUsdzUrl()) ios-src="{{ $software->modelUsdzUrl() }}" @endif
                            @if ($software->modelPosterUrl()) poster="{{ $software->modelPosterUrl() }}" @endif
                            alt="{{ $software->name }}"
                            camera-controls touch-action="pan-y"
                            :auto-rotate="auto"
                            ar ar-modes="webxr scene-viewer quick-look"
                            shadow-intensity="1" exposure="1" environment-image="neutral"
                            loading="lazy"
                            style="width:100%; height:100%; background:#f1f5f9">
                            <button slot="ar-button"
                                    class="absolute bottom-3 start-3 inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold text-white shadow-lg"
                                    style="background:#006C35">
                                <i class="fa-solid fa-vr-cardboard"></i> {{ __('software.model_ar') }}
                            </button>
                        </model-viewer>

                        {{-- Sketchfab-style control bar --}}
                        <div class="absolute bottom-3 end-3 flex items-center gap-0.5 rounded-full bg-black/55 p-1 text-white backdrop-blur">
                            @php($btn = 'flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/25')
                            <button type="button" @click.stop="auto = !auto" class="{{ $btn }}" :title="auto ? '{{ __('software.model_pause') }}' : '{{ __('software.model_play') }}'">
                                <i class="fa-solid" :class="auto ? 'fa-pause' : 'fa-play'"></i>
                            </button>
                            <button type="button" @click.stop="reset()" class="{{ $btn }}" title="{{ __('software.model_reset') }}">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                            <button type="button" @click.stop="help = !help" class="{{ $btn }}" title="{{ __('software.model_controls') }}">
                                <i class="fa-solid fa-circle-question"></i>
                            </button>
                            <button type="button" @click.stop="fullscreen()" class="{{ $btn }}" :title="fs ? '{{ __('software.model_exit_fs') }}' : '{{ __('software.model_fs') }}'">
                                <i class="fa-solid" :class="fs ? 'fa-compress' : 'fa-expand'"></i>
                            </button>
                        </div>

                        {{-- Help popover --}}
                        <div x-show="help" x-cloak @click.outside="help = false"
                             class="absolute bottom-14 end-3 w-56 rounded-xl bg-black/80 p-3 text-xs text-white backdrop-blur" style="display:none">
                            <div class="mb-1.5 font-bold">{{ __('software.model_controls') }}</div>
                            <ul class="space-y-1.5 text-white/80">
                                <li><i class="fa-solid fa-arrows-up-down-left-right w-4"></i> {{ __('software.model_help_rotate') }}</li>
                                <li><i class="fa-solid fa-magnifying-glass w-4"></i> {{ __('software.model_help_zoom') }}</li>
                                <li><i class="fa-solid fa-expand w-4"></i> {{ __('software.model_help_fs') }}</li>
                                <li><i class="fa-solid fa-vr-cardboard w-4"></i> {{ __('software.model_help_ar') }}</li>
                            </ul>
                        </div>
                    </div>
                    @once
                        @push('scripts')
                            <script type="module" src="https://cdn.jsdelivr.net/npm/@google/model-viewer@3.5.0/dist/model-viewer.min.js"></script>
                            <script>
                                function fnoonModel3d() {
                                    return {
                                        auto: true, help: false, fs: false,
                                        reset() {
                                            const mv = this.$refs.mv; if (!mv) return;
                                            try { mv.resetTurntableRotation(); } catch (e) {}
                                            mv.cameraOrbit = '0deg 75deg auto';
                                            mv.fieldOfView = 'auto';
                                            if (mv.jumpCameraToGoal) mv.jumpCameraToGoal();
                                        },
                                        fullscreen() {
                                            const el = this.$refs.wrap;
                                            if (!document.fullscreenElement) {
                                                (el.requestFullscreen || el.webkitRequestFullscreen || function () {}).call(el);
                                            } else { document.exitFullscreen(); }
                                        },
                                    };
                                }
                            </script>
                        @endpush
                    @endonce
                @endif
                </div>
            @endif

            {{-- Code viewer --}}
            @if ($software->hasCode())
                <div id="code" data-spy class="card-luxury overflow-hidden p-0 scroll-mt-32"
                     x-data="{ copied: false, copy() { window.fnoonCopy(this.$refs.code.innerText).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800) }) } }">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                        <h2 class="font-cairo font-bold text-base flex items-center gap-2">
                            <i class="fa-solid fa-code text-saudi-green"></i> {{ __('software.section.code') }}
                            @if ($software->code_language)
                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-mono uppercase text-gray-500" dir="ltr">{{ $software->code_language }}</span>
                            @endif
                        </h2>
                        <button type="button" @click="copy()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                            <i class="fa-solid" :class="copied ? 'fa-check text-saudi-green' : 'fa-copy'"></i>
                            <span x-text="copied ? '{{ __('software.code.copied') }}' : '{{ __('software.code.copy') }}'"></span>
                        </button>
                    </div>
                    <pre class="!m-0 max-h-[520px] overflow-auto !rounded-none text-sm leading-relaxed" dir="ltr"><code x-ref="code" class="language-{{ $software->codeLang() }}">{{ $software->code }}</code></pre>
                </div>
            @endif

            {{-- Requirements --}}
            @if ($software->requirements->isNotEmpty())
                <div id="requirements" data-spy class="card-luxury p-6 scroll-mt-32">
                    <h2 class="font-cairo font-bold text-xl mb-4">{{ __('site.requirements') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <tbody>
                                @foreach ($software->requirements as $req)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 font-semibold capitalize">{{ $req->os }} ({{ $req->tier }})</td>
                                        <td class="py-2 text-gray-600">{{ $req->processor }} · {{ $req->memory }} · {{ $req->storage }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- User feedback: reviews + comments as tabs in one card --}}
            <div id="reviews" data-spy class="card-luxury p-6 scroll-mt-32"
                 x-data="{ tab: 'reviews' }" x-init="if (location.hash === '#comments') tab = 'comments'">

                {{-- Tab headers --}}
                <div class="mb-5 flex items-center gap-1 border-b border-gray-100">
                    <button type="button" @click="tab='reviews'"
                            :class="tab==='reviews' ? 'border-saudi-green text-saudi-green' : 'border-transparent text-gray-400 hover:text-gray-600'"
                            class="-mb-px flex items-center gap-2 border-b-2 px-3 pb-3 font-cairo font-bold transition">
                        <i class="fa-solid fa-star text-royal-gold"></i> {{ __('site.reviews.title') }}
                        @if ((int) $software->reviews_count > 0)<span class="text-xs font-normal text-gray-400">({{ $software->reviews_count }})</span>@endif
                    </button>
                    <button type="button" @click="tab='comments'"
                            :class="tab==='comments' ? 'border-saudi-green text-saudi-green' : 'border-transparent text-gray-400 hover:text-gray-600'"
                            class="-mb-px flex items-center gap-2 border-b-2 px-3 pb-3 font-cairo font-bold transition">
                        <i class="fa-solid fa-comments text-saudi-green"></i> {{ __('comment.title') }}
                        <span class="text-xs font-normal text-gray-400">({{ $software->approvedComments->count() }})</span>
                    </button>
                    @if ((int) $software->reviews_count > 0)
                        <span x-show="tab==='reviews'" class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold text-bronze" dir="ltr">
                            <i class="fa-solid fa-star text-royal-gold"></i> {{ number_format((float) $software->rating_avg, 1) }}
                        </span>
                    @endif
                </div>

                {{-- ===== Reviews tab ===== --}}
                <div x-show="tab==='reviews'">
                @forelse ($software->approvedReviews as $review)
                    <div class="border-b border-gray-100 py-3">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">{{ $review->authorName() }}</span>
                            <span class="text-royal-gold text-sm" dir="ltr">
                                @for ($i = 1; $i <= 5; $i++)<i class="fa-{{ $i <= $review->rating ? 'solid' : 'regular' }} fa-star"></i>@endfor
                            </span>
                        </div>
                        @if ($review->title)<p class="font-medium mt-1">{{ $review->title }}</p>@endif
                        <p class="text-sm text-gray-600 mt-1">{{ $review->body }}</p>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">{{ __('site.reviews.none') }}</p>
                @endforelse

                {{-- Add a review --}}
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <h3 class="font-bold mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-star text-royal-gold"></i> {{ __('review.add_title') }}
                    </h3>
                    @include('partials.review-form', ['software' => $software])
                </div>
                </div>{{-- /reviews tab --}}

                {{-- ===== Comments tab ===== --}}
                <div id="comments" x-show="tab==='comments'" x-cloak class="scroll-mt-32">
                @if (session('comment_status'))
                    <div class="mb-5 flex items-center gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">
                        <i class="fa-solid fa-circle-check"></i> {{ session('comment_status') }}
                    </div>
                @endif

                {{-- List --}}
                <div class="space-y-5">
                    @forelse ($software->approvedComments as $comment)
                        <div class="flex gap-3">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-saudi-green/10 font-bold text-saudi-green">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->displayName(), 0, 1)) }}
                            </span>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-800">{{ $comment->displayName() }}</span>
                                    @if ($comment->user_id)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-saudi-green/10 text-saudi-green"><i class="fa-solid fa-circle-check"></i> {{ __('comment.member') }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400" dir="ltr">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1 text-sm leading-relaxed text-gray-600 whitespace-pre-line">{{ $comment->body }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm">{{ __('comment.none') }}</p>
                    @endforelse
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('comments.store', $software) }}"
                      class="mt-7 space-y-3 border-t border-gray-100 pt-6">
                    @csrf
                    {{-- honeypot --}}
                    <div class="hidden" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <h3 class="font-cairo font-bold text-base">{{ __('comment.form_title') }}</h3>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <input type="text" name="author_name" value="{{ old('author_name', auth()->user()?->name) }}" required maxlength="80"
                                   placeholder="{{ __('comment.name') }}"
                                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">
                            @error('author_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <input type="email" name="author_email" value="{{ old('author_email', auth()->user()?->email) }}" maxlength="160"
                                   placeholder="{{ __('comment.your_email') }}"
                                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">
                            @error('author_email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <textarea name="body" rows="3" required minlength="3" maxlength="2000"
                                  placeholder="{{ __('comment.your_comment') }}"
                                  class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/20 focus:outline-none">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> {{ __('comment.submit') }}
                    </button>
                </form>
                </div>{{-- /comments tab --}}
            </div>{{-- /user-feedback card --}}
        </div>

        {{-- Sidebar: download --}}
        <aside class="space-y-6" id="fnoon-sidebar">
            <div class="card-luxury overflow-hidden">
                @php($primary = $software->downloadLinks->first())
                @php($osIcons = ['windows' => 'fa-brands fa-windows', 'macos' => 'fa-brands fa-apple', 'linux' => 'fa-brands fa-linux', 'android' => 'fa-brands fa-android', 'ios' => 'fa-brands fa-apple', 'web' => 'fa-solid fa-globe'])
                @if ($primary)
                    @php($links = $software->downloadLinks)
                    @php($multi = $links->count() > 1)
                    {{-- Header: license / price + size --}}
                    <div class="relative bg-gradient-to-br from-saudi-green to-[#00582b] px-6 pt-6 pb-5 text-white">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ __('site.download.license') }}</span>
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold backdrop-blur-sm">
                                @if ($software->isPaid())
                                    <span dir="ltr">${{ rtrim(rtrim((string) $software->price, '0'), '.') }}</span>
                                @else
                                    {{ __('content.license.'.$software->license_type) }}
                                @endif
                            </span>
                        </div>
                        <div class="mt-3 font-cairo text-3xl font-black leading-none" dir="ltr">{{ $multi ? \Illuminate\Support\Number::fileSize((int) $links->sum('size_bytes'), precision: 2) : $primary->humanSize() }}</div>
                        <div class="mt-1 text-sm text-white/70">
                            {{ __('site.download.size') }}@if ($multi) · {{ __('site.download.parts', ['n' => $links->count()]) }}@endif
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($software->download_requires_login && ! auth()->check())
                            {{-- Private content — download links gated behind login --}}
                            <div class="rounded-2xl border border-saudi-green/20 bg-saudi-green/5 px-5 py-7 text-center">
                                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-saudi-green/10 text-2xl text-saudi-green">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <h4 class="font-cairo text-lg font-black text-luxury-black">{{ __('site.download.private_title') }}</h4>
                                <p class="mx-auto mt-1.5 max-w-[16rem] text-sm leading-relaxed text-gray-500">{{ __('site.download.private_body') }}</p>
                                <a href="/dashboard/login?redirect={{ urlencode(route('software.show', $software)) }}" class="btn-primary mt-5 w-full justify-center text-base">
                                    <i class="fa-solid fa-right-to-bracket"></i> {{ __('site.download.private_login') }}
                                </a>
                                <a href="/dashboard/login" class="mt-2.5 inline-block text-xs font-semibold text-saudi-green hover:underline">{{ __('site.download.private_register') }}</a>
                            </div>
                        @else
                        @if ($multi)
                            {{-- One-click download of all parts (sequential, via hidden iframes) --}}
                            <div x-data="{ urls: @js($links->map(fn ($l) => route('download.start', [$software, $l]))->values()), copied: false,
                                          all() {
                                              // Hidden iframes keep a download-manager's capture page off the main tab,
                                              // so every part actually starts (a plain <a> click lets the manager navigate
                                              // the page away after the first file).
                                              this.urls.forEach((u, i) => setTimeout(() => { const f = document.createElement('iframe'); f.style.display='none'; f.src=u; document.body.appendChild(f); setTimeout(() => f.remove(), 120000); }, i * 1500));
                                          },
                                          copyAll() { window.fnoonCopy(this.urls.join('\n')); this.copied = true; setTimeout(() => this.copied = false, 1800); } }">
                                <button type="button" x-on:click="all()" data-dl-all
                                        class="btn-primary w-full justify-center text-base shadow-lg shadow-saudi-green/20">
                                    <i class="fa-solid fa-cloud-arrow-down"></i> {{ __('site.download.all', ['n' => $links->count()]) }}
                                </button>
                                <button type="button" x-on:click="copyAll()"
                                        class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-xl border border-saudi-green/30 px-3 py-2 text-xs font-semibold text-saudi-green transition hover:bg-saudi-green/5">
                                    <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copied ? '{{ __('site.download.links_copied') }}' : '{{ __('site.download.copy_links') }}'"></span>
                                </button>
                                <p class="mt-2 text-[11px] leading-relaxed text-gray-400">{{ __('site.download.all_note') }}</p>
                            </div>

                            {{-- Numbered parts (split archive — all parts required) --}}
                            <div class="space-y-2">
                                @foreach ($links as $i => $link)
                                    <a href="{{ route('download.gateway', [$software, $link]) }}"
                                       class="group flex items-center gap-3 rounded-xl border border-gray-100 px-3 py-2.5 transition hover:border-saudi-green/40 hover:bg-saudi-green/5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-saudi-green/10 text-base font-black text-saudi-green" dir="ltr">{{ $i + 1 }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-1.5">
                                                <span class="text-sm font-bold text-gray-800">{{ __('site.download.part', ['n' => $i + 1]) }}</span>
                                                @if (filled($link->note))
                                                    <span class="shrink-0 rounded-md bg-royal-gold/20 px-1.5 py-0.5 text-[10px] font-bold text-saudi-green">{{ $link->note }}</span>
                                                @endif
                                            </span>
                                            <span class="block text-[11px] text-gray-400" dir="ltr">{{ $link->humanSize() }}</span>
                                        </span>
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-saudi-green px-4 py-1.5 text-sm font-bold text-white shadow-sm transition group-hover:bg-saudi-green-dark">
                                            <i class="fa-solid fa-download"></i> {{ __('site.download.get') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                            <p class="mt-3 flex items-start gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-700">
                                <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i> {{ __('site.download.parts_note') }}
                            </p>
                        @else
                            {{-- Single download button --}}
                            <a href="{{ route('download.gateway', [$software, $primary]) }}"
                               class="btn-primary w-full justify-center text-lg shadow-lg shadow-saudi-green/20">
                                <i class="fa-solid fa-download"></i>
                                {{ $software->isPaid() ? __('site.download.now') : __('site.download.free') }}
                            </a>
                            <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-400">
                                <i class="fa-solid fa-shield-halved text-saudi-green"></i> {{ __('site.download.secure') }}
                            </p>
                        @endif
                        @endif

                        {{-- Meta grid --}}
                        <dl class="mt-5 divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="flex items-center gap-2 text-gray-500"><i class="fa-solid fa-code-branch w-4 text-center text-saudi-green/70"></i> {{ __('site.download.version') }}</dt>
                                <dd class="font-semibold text-gray-800" dir="ltr">{{ $software->current_version ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="flex items-center gap-2 text-gray-500"><i class="fa-solid fa-clock-rotate-left w-4 text-center text-saudi-green/70"></i> {{ __('site.download.updated') }}</dt>
                                <dd class="font-semibold text-gray-800" dir="ltr">{{ $software->published_at?->format('Y-m-d') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="flex items-center gap-2 text-gray-500"><i class="fa-solid fa-arrow-down-long w-4 text-center text-saudi-green/70"></i> {{ __('site.download.downloads') }}</dt>
                                <dd class="font-semibold text-gray-800" dir="ltr">{{ number_format($software->downloads_count) }}</dd>
                            </div>
                            @if (is_array($software->os_support) && count($software->os_support))
                                <div class="flex items-center justify-between py-2.5">
                                    <dt class="flex items-center gap-2 text-gray-500"><i class="fa-solid fa-desktop w-4 text-center text-saudi-green/70"></i> {{ __('site.download.platforms') }}</dt>
                                    <dd class="flex items-center gap-2 text-gray-500" dir="ltr">
                                        @foreach (array_slice($software->os_support, 0, 5) as $os)
                                            <i class="{{ $osIcons[$os] ?? 'fa-solid fa-desktop' }}" title="{{ $os }}"></i>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                        </dl>

                    </div>
                @else
                    <div class="p-6">
                        <p class="text-center text-sm text-gray-400">{{ __('site.empty') }}</p>
                    </div>
                @endif
            </div>

            @if ($software->tags->isNotEmpty())
                <div class="card-luxury p-6">
                    <h3 class="mb-3 flex items-center gap-2 font-cairo text-sm font-bold text-gray-700">
                        <i class="fa-solid fa-tags text-saudi-green"></i> {{ __('site.tags') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($software->tags as $tag)
                            <a href="{{ route('search', ['q' => $tag->name]) }}"
                               class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-saudi-green/10 hover:text-saudi-green">
                                <i class="fa-solid fa-hashtag text-[10px] opacity-50"></i>{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-ad placement="sidebar" />
        </aside>
    </div>

    {{-- Related --}}
    @if ($related->isNotEmpty())
        <div class="mt-12">
            @include('partials.software-row', ['title' => __('site.sections.related'), 'items' => $related])
        </div>
    @endif

    {{-- Screenshot lightbox (with prev/next + keyboard navigation) --}}
    <div x-show="lb" x-cloak style="display:none"
         x-transition.opacity
         @keydown.escape.window="lb=false"
         @keydown.arrow-right.window="prev()"
         @keydown.arrow-left.window="next()"
         @click="lb=false"
         class="fixed inset-0 z-[80] flex select-none items-center justify-center bg-black/90 p-4 sm:p-8">

        {{-- Full-resolution image --}}
        <img :src="cur.src" @click.stop alt=""
             class="max-h-[90vh] max-w-full rounded-xl object-contain shadow-2xl">

        {{-- Caption + counter --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-5 flex flex-col items-center gap-1.5 px-4 text-center text-white">
            <span x-show="cur.cap" x-text="cur.cap" class="max-w-2xl rounded-lg bg-black/55 px-3 py-1 text-sm backdrop-blur"></span>
            <span x-show="imgs.length > 1" class="rounded-full bg-black/55 px-2.5 py-0.5 text-xs text-white/80" dir="ltr">
                <span x-text="i + 1"></span> / <span x-text="imgs.length"></span>
            </span>
        </div>

        {{-- Prev / next (only when there's more than one) --}}
        <template x-if="imgs.length > 1">
            <div>
                <button type="button" @click.stop="prev()" aria-label="Previous"
                        class="absolute start-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-xl text-white transition hover:bg-white/25 sm:h-12 sm:w-12">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" @click.stop="next()" aria-label="Next"
                        class="absolute end-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-xl text-white transition hover:bg-white/25 sm:h-12 sm:w-12">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            </div>
        </template>

        {{-- Close --}}
        <button type="button" @click="lb=false" aria-label="Close"
                class="absolute top-5 end-5 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/25">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
@endsection

@push('styles')
    <style>
        html { scroll-behavior: smooth; }
        @media (min-width: 1024px) {
            #fnoon-sidebar { position: sticky; align-self: start; top: 5rem; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Smart sticky sidebar: sticks to the top normally, but when the sidebar
        // is taller than the viewport it sticks to the BOTTOM instead — so the
        // tags at the end stay reachable rather than hiding behind the download box.
        (function () {
            const el = document.getElementById('fnoon-sidebar');
            if (! el) return;
            const TOP = 80, BOTTOM = 24;
            const apply = () => {
                if (window.innerWidth < 1024) { el.style.top = ''; return; }
                const vh = window.innerHeight, h = el.offsetHeight;
                el.style.top = Math.min(TOP, vh - h - BOTTOM) + 'px';
            };
            apply();
            window.addEventListener('resize', apply, { passive: true });
            if (window.ResizeObserver) new ResizeObserver(apply).observe(el);
            setTimeout(apply, 600);
        })();
    </script>
@endpush

@if ($software->hasCode())
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
        <style>
            pre[class*="language-"], .card-luxury pre { margin: 0; border-radius: 0; }
            pre code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
        <script>window.Prism = window.Prism || {}; Prism.manual = false;</script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    @endpush
@endif
