@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Models\Setting::text('site_name', config('app.name'))) — {{ \App\Models\Setting::text('tagline', __('site.tagline')) }}</title>
    <meta name="description" content="@yield('meta_description', \App\Models\Setting::text('hero_subtitle', __('site.hero.subtitle')))">

    {{-- SEO: canonical + Open Graph + Twitter Card + structured data --}}
    @php($seoName = \App\Models\Setting::text('site_name', config('app.name')))
    @php($seoTitle = trim($__env->yieldContent('og_title')) ?: $seoName)
    @php($seoDesc = trim($__env->yieldContent('og_description')) ?: \App\Models\Setting::text('hero_subtitle', __('site.hero.subtitle')))
    @php($seoImage = trim($__env->yieldContent('og_image')) ?: (\App\Support\SiteBranding::logo() ?: asset('favicon.ico')))
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $seoName }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_AR' : 'en_US' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    @stack('jsonld')

    {{-- Favicon (admin-configurable, falls back to the bundled icon) --}}
    @php($favicon = \App\Support\SiteBranding::favicon())
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}">
        <link rel="shortcut icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @endif

    {{-- Google AdSense (admin-toggled). Auto Ads place themselves; manual units use <x-ad>. --}}
    @php($ads = app(\App\Support\Ads::class))
    @if ($ads->enabled())
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $ads->publisherId() }}" crossorigin="anonymous"></script>
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Tajawal:wght@300;400;500;700;900&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Tailwind (CDN, no build step) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'saudi-green': 'rgb(var(--c-primary) / <alpha-value>)',
                        'saudi-green-dark': 'var(--color-primary-dark)',
                        'royal-gold': 'rgb(var(--c-secondary) / <alpha-value>)',
                        'royal-gold-dark': 'var(--color-secondary-dark)',
                        'luxury-black': '#1A1A1A',
                        'bronze': 'rgb(var(--c-accent) / <alpha-value>)',
                    },
                    fontFamily: {
                        body: ['Tajawal', 'Cairo', 'sans-serif'],
                        cairo: ['Cairo', 'Tajawal', 'sans-serif'],
                        playfair: ['Playfair Display', 'serif'],
                    },
                    boxShadow: {
                        luxury: '0 20px 60px -15px rgb(var(--c-primary) / .25)',
                        gold: '0 10px 30px -10px rgb(var(--c-secondary) / .4)',
                    },
                },
            },
        };
    </script>

    <style>
        {!! \App\Support\Theme::cssRoot() !!}
        html { scrollbar-gutter: stable; }
        body { font-family: 'Tajawal','Cairo',sans-serif; background: #FBFAF6; color: #1A1A1A; }
        h1,h2,h3,h4,h5 { font-family: 'Cairo','Tajawal',sans-serif; }
        ::selection { background: var(--color-secondary); color: #000; }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: #fff; border-radius: .75rem; padding: .65rem 1.4rem; font-weight: 700;
            box-shadow: 0 10px 25px -10px rgb(var(--c-primary) / .5); transition: .3s ease; display: inline-flex;
            align-items: center; gap: .5rem;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 32px -12px rgb(var(--c-primary) / .6); }
        .btn-gold {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-secondary-dark));
            color: #1A1A1A; border-radius: .75rem; padding: .65rem 1.4rem; font-weight: 700; transition: .3s ease;
            display: inline-flex; align-items: center; gap: .5rem;
        }
        .btn-gold:hover { transform: translateY(-2px); }
        .btn-outline {
            border: 1.5px solid rgb(var(--c-secondary) / .6); color: var(--color-accent); border-radius: .75rem;
            padding: .6rem 1.3rem; font-weight: 700; transition: .3s ease; display: inline-flex; align-items: center; gap: .5rem;
        }
        .btn-outline:hover { background: var(--color-secondary); color: #1A1A1A; }
        .card-luxury {
            background: #fff; border: 1px solid rgb(var(--c-secondary) / .18); border-radius: 1rem;
            box-shadow: 0 8px 30px -18px rgb(var(--c-primary) / .25); transition: .3s ease;
        }
        .card-luxury:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -18px rgb(var(--c-primary) / .35); }
        .hero-pattern {
            background:
                radial-gradient(circle at 15% 20%, rgb(var(--c-secondary) / .18), transparent 40%),
                radial-gradient(circle at 85% 30%, rgb(var(--c-primary) / .18), transparent 40%),
                linear-gradient(135deg, var(--color-primary-darker), #1A1A1A);
        }
        .gold-divider { height: 1px; background: linear-gradient(90deg, transparent, rgb(var(--c-secondary) / .6), transparent); }

        .hero-glass {
            background: rgba(255,255,255,.07);
            backdrop-filter: blur(14px);
            border: 1px solid rgb(var(--c-secondary) / .28);
            box-shadow: 0 30px 80px -30px rgba(0,0,0,.6);
        }
        .hero-grid {
            background-image:
                linear-gradient(rgb(var(--c-secondary) / .06) 1px, transparent 1px),
                linear-gradient(90deg, rgb(var(--c-secondary) / .06) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .float-anim { animation: floaty 5s ease-in-out infinite; }
        @keyframes floaty { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
        .fade-up { animation: fadeUp .6s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px) } to { opacity: 1; transform: translateY(0) } }
        .chip {
            display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem;
            border-radius: 9999px; font-size: .8rem; font-weight: 600;
            background: rgba(255,255,255,.08); border: 1px solid rgb(var(--c-secondary) / .3); color: #f5efe0;
        }
        .type-card {
            position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.5rem;
            background: #fff; border: 1px solid rgb(var(--c-secondary) / .2); transition: .35s ease; display: block;
        }
        .type-card:hover { transform: translateY(-6px); box-shadow: 0 24px 50px -24px rgb(var(--c-primary) / .4); border-color: rgb(var(--c-secondary) / .55); }
        .type-card::after {
            content: ''; position: absolute; inset-inline-end: -30px; top: -30px; width: 120px; height: 120px;
            border-radius: 9999px; background: radial-gradient(circle, rgb(var(--c-primary) / .12), transparent 70%); transition: .35s;
        }
        .type-card:hover::after { transform: scale(1.6); }
        .section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
        .view-all { font-size: .85rem; font-weight: 700; color: var(--color-primary); display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap; }
        .view-all:hover { color: var(--color-primary-dark); }
        .stat-num { font-variant-numeric: tabular-nums; }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col antialiased">
    @include('partials.header')

    <x-ad placement="header" :label="false" class="max-w-7xl mx-auto w-full px-4 mt-3" />

    <main class="flex-1">
        @if (session('status'))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <x-ad placement="footer" class="max-w-7xl mx-auto w-full px-4 mb-6" />

    @include('partials.footer')

    {{-- Report-a-problem widget (floating button + screenshot modal) --}}
    @include('partials.report-problem')

    {{-- Global custom <x-select> behaviour (defined before deferred Alpine runs) --}}
    <script>
        function fnoonSelect(config) {
            const items = Object.entries(config.options || {}).map(([value, label]) => ({ value: String(value), label }));
            if (config.placeholder) {
                items.unshift({ value: '', label: config.placeholder });
            }
            return {
                open: false,
                search: '',
                value: String(config.value ?? ''),
                items,
                searchable: !!config.searchable,
                get label() {
                    const found = this.items.find(i => i.value === this.value);
                    return found ? found.label : (config.placeholder || '');
                },
                get filtered() {
                    if (!this.searchable || !this.search) return this.items;
                    const q = this.search.toLowerCase();
                    return this.items.filter(i => i.label.toLowerCase().includes(q));
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) this.search = '';
                },
                choose(v) {
                    this.value = v;
                    this.open = false;
                    if (config.submitOnChange) {
                        this.$nextTick(() => this.$refs.input.form && this.$refs.input.form.submit());
                    }
                },
            };
        }
    </script>

    {{-- Clipboard helper (works on http where navigator.clipboard is unavailable) --}}
    <script>
        window.fnoonCopy = function (text) {
            text = (text === null || text === undefined) ? '' : String(text);
            var legacy = function () {
                return new Promise(function (resolve) {
                    var ta = document.createElement('textarea');
                    ta.value = text; ta.setAttribute('readonly', '');
                    ta.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;padding:0;margin:0;border:0;opacity:0;z-index:2147483647;';
                    document.body.appendChild(ta);
                    var sel = document.getSelection();
                    var prev = (sel && sel.rangeCount) ? sel.getRangeAt(0) : null;
                    ta.focus({ preventScroll: true }); ta.select();
                    try { ta.setSelectionRange(0, text.length); } catch (e) {}
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(ta);
                    if (prev && sel) { try { sel.removeAllRanges(); sel.addRange(prev); } catch (e) {} }
                    resolve();
                });
            };
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).catch(legacy);
            }
            return legacy();
        };
    </script>

    {{-- Alpine plugins must load before the core --}}
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @stack('scripts')
</body>
</html>
