@php
    use App\Models\Setting;
    $siteName = Setting::text('site_name', __('site.name'));
    $siteLogo = \App\Support\SiteBranding::logo();
    $socials = [
        'social_twitter' => 'fa-brands fa-x-twitter',
        'social_facebook' => 'fa-brands fa-facebook-f',
        'social_instagram' => 'fa-brands fa-instagram',
        'social_youtube' => 'fa-brands fa-youtube',
        'social_github' => 'fa-brands fa-github',
    ];
    $contactEmail = Setting::get('contact_email');
    $contactPhone = Setting::get('contact_phone');
@endphp
<footer class="mt-16 bg-luxury-black text-gray-300">
    <div class="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <div class="flex items-center gap-2 mb-3">
                @if ($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 w-auto max-h-9 object-contain">
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-saudi-green text-white">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </span>
                    <span class="font-cairo font-black text-xl text-white">{{ $siteName }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-400 leading-relaxed">{{ Setting::text('footer_about', __('site.footer.about')) }}</p>

            {{-- Social links (only those configured in the admin) --}}
            <div class="flex items-center gap-2 mt-4" dir="ltr">
                @foreach ($socials as $key => $icon)
                    @if ($url = Setting::get($key))
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="h-9 w-9 inline-flex items-center justify-center rounded-lg bg-white/10 hover:bg-royal-gold hover:text-luxury-black transition">
                            <i class="{{ $icon }}"></i>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="text-white font-bold mb-3">{{ __('site.footer.links') }}</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('browse') }}" class="hover:text-royal-gold">{{ __('site.nav.browse') }}</a></li>
                <li><a href="{{ route('blog.index') }}" class="hover:text-royal-gold">{{ __('site.nav.blog') }}</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-royal-gold">{{ __('site.nav.contact') }}</a></li>
                <li><a href="/upload" class="hover:text-royal-gold">{{ __('site.nav.upload') }}</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-white font-bold mb-3">{{ __('site.footer.legal') }}</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="/about" class="hover:text-royal-gold">About</a></li>
                <li><a href="/privacy" class="hover:text-royal-gold">Privacy</a></li>
                <li><a href="/terms" class="hover:text-royal-gold">Terms</a></li>
            </ul>
            @if ($contactEmail || $contactPhone)
                <div class="mt-4 space-y-1 text-sm text-gray-400" dir="ltr">
                    @if ($contactEmail)<div><i class="fa-solid fa-envelope text-royal-gold"></i> {{ $contactEmail }}</div>@endif
                    @if ($contactPhone)<div><i class="fa-solid fa-phone text-royal-gold"></i> {{ $contactPhone }}</div>@endif
                </div>
            @endif
        </div>

        <div>
            <h4 class="text-white font-bold mb-3">{{ __('site.footer.newsletter') }}</h4>
            <p class="text-sm text-gray-400 mb-3">{{ __('site.footer.newsletter_text') }}</p>
            <form action="{{ route('newsletter.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="email" name="email" required placeholder="you@email.com"
                       class="flex-1 min-w-0 rounded-lg bg-white/10 border border-white/20 px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-royal-gold">
                <button class="btn-gold text-sm">{{ __('site.footer.subscribe') }}</button>
            </form>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-5 text-center text-sm text-gray-500">
            © {{ now()->year }} {{ $siteName }}. {{ __('site.footer.rights') }}
        </div>
    </div>
</footer>
