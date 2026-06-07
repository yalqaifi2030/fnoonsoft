@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ar';
    $untilIso = ! empty($until) ? \Illuminate\Support\Carbon::parse($until)->toIso8601String() : null;
    $socialIcons = [
        'twitter' => 'fa-brands fa-x-twitter',
        'facebook' => 'fa-brands fa-facebook-f',
        'instagram' => 'fa-brands fa-instagram',
        'youtube' => 'fa-brands fa-youtube',
        'github' => 'fa-brands fa-github',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --green:#006C35; --green-dark:#00582b; --gold:#C9A961; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Tajawal','Segoe UI',system-ui,-apple-system,sans-serif;
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            color:#e5e7eb; padding:1.5rem; position:relative; overflow:hidden;
            background:#06140d;
        }
        /* animated gradient blobs */
        .bg { position:fixed; inset:0; z-index:0; overflow:hidden; }
        .bg::before, .bg::after {
            content:''; position:absolute; border-radius:50%; filter:blur(90px); opacity:.55;
            animation:float 14s ease-in-out infinite;
        }
        .bg::before { width:46rem; height:46rem; background:radial-gradient(circle,#0a8f47,transparent 60%); top:-12rem; inset-inline-start:-10rem; }
        .bg::after  { width:40rem; height:40rem; background:radial-gradient(circle,#0b6f3c,transparent 60%); bottom:-12rem; inset-inline-end:-8rem; animation-delay:-7s; }
        @keyframes float { 0%,100%{ transform:translateY(0) scale(1);} 50%{ transform:translateY(-30px) scale(1.06);} }
        .grid-overlay { position:fixed; inset:0; z-index:0; opacity:.06;
            background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);
            background-size:44px 44px; }

        .card {
            position:relative; z-index:1; width:100%; max-width:40rem; text-align:center;
            background:rgba(12,28,20,.55); border:1px solid rgba(201,169,97,.22);
            border-radius:1.75rem; padding:3rem 2rem;
            backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
            box-shadow:0 40px 80px -30px rgba(0,0,0,.7);
        }
        .badge {
            width:5.5rem; height:5.5rem; margin:0 auto 1.5rem; border-radius:1.4rem;
            background:linear-gradient(135deg,var(--green),var(--green-dark));
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 18px 36px -12px rgba(0,108,53,.8); position:relative;
        }
        .badge i { font-size:2.4rem; color:#fff; animation:wrench 3s ease-in-out infinite; }
        @keyframes wrench { 0%,100%{ transform:rotate(0);} 25%{ transform:rotate(-12deg);} 75%{ transform:rotate(12deg);} }
        .brand { font-size:.78rem; font-weight:800; letter-spacing:.2em; color:var(--gold); text-transform:uppercase; margin-bottom:.7rem; }
        h1 { font-size:2rem; font-weight:900; color:#fff; line-height:1.25; }
        .msg { font-size:1.02rem; line-height:1.8; color:#9fb3a8; margin-top:1rem; max-width:32rem; margin-inline:auto; }

        .countdown { display:flex; gap:.75rem; justify-content:center; margin-top:2rem; }
        .cd-box {
            min-width:4.5rem; padding:.9rem .6rem; border-radius:1rem;
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
        }
        .cd-num { font-size:1.9rem; font-weight:900; color:#fff; line-height:1; font-variant-numeric:tabular-nums; }
        .cd-lbl { margin-top:.4rem; font-size:.66rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--gold); }

        .divider { height:1px; background:linear-gradient(90deg,transparent,rgba(201,169,97,.3),transparent); margin:2rem 0 1.5rem; }
        .social { display:flex; gap:.6rem; justify-content:center; }
        .social a {
            width:2.7rem; height:2.7rem; border-radius:.85rem; display:flex; align-items:center; justify-content:center;
            color:#cbd5cd; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08);
            text-decoration:none; transition:all .2s ease;
        }
        .social a:hover { color:#fff; background:var(--green); transform:translateY(-3px); border-color:transparent; }
        .contact { margin-top:1.25rem; font-size:.9rem; color:#9fb3a8; }
        .contact a { color:var(--gold); text-decoration:none; font-weight:700; }
        .foot { margin-top:1.75rem; font-size:.75rem; color:#5b6b63; }
    </style>
</head>
<body>
    <div class="bg"></div>
    <div class="grid-overlay"></div>

    <div class="card">
        <div class="badge"><i class="fa-solid fa-screwdriver-wrench"></i></div>

        <div class="brand">{{ $siteName }}</div>
        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>

        @if ($untilIso)
            <div class="countdown" id="countdown" data-until="{{ $untilIso }}">
                <div class="cd-box"><div class="cd-num" id="cd-d">00</div><div class="cd-lbl">{{ __('site.maintenance.days') }}</div></div>
                <div class="cd-box"><div class="cd-num" id="cd-h">00</div><div class="cd-lbl">{{ __('site.maintenance.hours') }}</div></div>
                <div class="cd-box"><div class="cd-num" id="cd-m">00</div><div class="cd-lbl">{{ __('site.maintenance.minutes') }}</div></div>
                <div class="cd-box"><div class="cd-num" id="cd-s">00</div><div class="cd-lbl">{{ __('site.maintenance.seconds') }}</div></div>
            </div>
        @endif

        @if (! empty($social) || ! empty($email))
            <div class="divider"></div>
            @if (! empty($social))
                <div class="social">
                    @foreach ($social as $platform => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $platform }}">
                            <i class="{{ $socialIcons[$platform] ?? 'fa-solid fa-link' }}"></i>
                        </a>
                    @endforeach
                </div>
            @endif
            @if (! empty($email))
                <p class="contact">{{ __('site.maintenance.contact') }} <a href="mailto:{{ $email }}" dir="ltr">{{ $email }}</a></p>
            @endif
        @endif

        <div class="foot">© {{ date('Y') }} {{ $siteName }}</div>
    </div>

    @if ($untilIso)
        <script>
            (function () {
                var el = document.getElementById('countdown');
                var target = new Date(el.getAttribute('data-until')).getTime();
                var pad = function (n) { return String(n).padStart(2, '0'); };
                function tick() {
                    var diff = Math.max(0, target - Date.now());
                    var d = Math.floor(diff / 86400000); diff -= d * 86400000;
                    var h = Math.floor(diff / 3600000); diff -= h * 3600000;
                    var m = Math.floor(diff / 60000); diff -= m * 60000;
                    var s = Math.floor(diff / 1000);
                    document.getElementById('cd-d').textContent = pad(d);
                    document.getElementById('cd-h').textContent = pad(h);
                    document.getElementById('cd-m').textContent = pad(m);
                    document.getElementById('cd-s').textContent = pad(s);
                }
                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endif
</body>
</html>
