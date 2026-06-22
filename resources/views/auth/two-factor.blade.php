<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('security.challenge_title') }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:'Tajawal',system-ui,sans-serif}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#06140d] to-[#0b3522] flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-5 text-center">
                @php($logo = \App\Support\SiteBranding::logo())
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="mx-auto mb-4 h-12 w-auto object-contain">
                @endif
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl text-2xl" style="background:rgba(0,108,53,.1); color:#006C35">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
                <h1 class="font-bold text-xl text-gray-900">{{ __('security.challenge_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('security.challenge_hint') }}</p>
            </div>

            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-3">
                @csrf
                <input type="text" name="code" inputmode="text" autocomplete="one-time-code" autofocus dir="ltr"
                       placeholder="000000"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 text-center font-mono text-lg tracking-[0.3em] focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20 focus:outline-none">
                @error('code')<p class="text-center text-xs text-red-600">{{ $message }}</p>@enderror

                <button class="w-full rounded-xl py-3 font-bold text-white transition hover:brightness-110" style="background:#006C35">
                    {{ __('security.verify') }}
                </button>
            </form>

            <p class="mt-3 text-center text-xs text-gray-400">{{ __('security.challenge_recovery') }}</p>

            <div class="mt-5 border-t border-gray-100 pt-4 text-center">
                <form method="POST" action="{{ route('two-factor.logout') }}">
                    @csrf
                    <button class="text-sm text-gray-400 transition hover:text-red-500">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> {{ __('security.sign_out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
