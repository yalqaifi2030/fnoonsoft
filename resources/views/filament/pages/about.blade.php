<x-filament-panels::page>
    {{-- ===== Hero ===== --}}
    <div style="background:linear-gradient(120deg,#006C35 0%,#00582b 55%,#0f1419 100%);"
         class="relative overflow-hidden rounded-2xl text-white">
        <div class="absolute inset-0 opacity-40"
             style="background-image:linear-gradient(rgba(201,169,97,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(201,169,97,.08) 1px,transparent 1px);background-size:42px 42px;"></div>
        <div class="absolute -top-16 -end-10 h-56 w-56 rounded-full"
             style="background:radial-gradient(circle,rgba(201,169,97,.25),transparent 70%);"></div>

        <div class="relative" style="padding:1.75rem 2rem;">
            <div style="display:flex;align-items:center;gap:1.1rem;flex-wrap:wrap;">
                <span style="display:flex;height:4rem;width:4rem;flex:0 0 auto;align-items:center;justify-content:center;border-radius:1.1rem;background:rgba(255,255,255,.1);box-shadow:inset 0 0 0 1px rgba(255,255,255,.15);">
                    <i class="fa-solid fa-layer-group" style="font-size:1.6rem;color:#C9A961;"></i>
                </span>
                <div>
                    <p style="font-size:.8rem;color:#bbf7d0;">{{ __('about.subtitle') }}</p>
                    <h2 style="margin-top:.15rem;font-size:1.9rem;font-weight:800;line-height:1.05;">{{ $siteName }}</h2>
                    <div style="margin-top:.6rem;display:flex;gap:.5rem;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;gap:.4rem;border-radius:9999px;background:#C9A961;color:#1a1205;padding:.25rem .8rem;font-size:.78rem;font-weight:800;" dir="ltr">
                            <i class="fa-solid fa-code-branch"></i> v{{ $version }}
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:.4rem;border-radius:9999px;background:rgba(255,255,255,.1);padding:.25rem .8rem;font-size:.75rem;box-shadow:inset 0 0 0 1px rgba(255,255,255,.15);">
                            <i class="fa-solid fa-star" style="color:#fcd34d;"></i> {{ $codename }}
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:.4rem;border-radius:9999px;background:rgba(255,255,255,.08);padding:.25rem .8rem;font-size:.75rem;color:#d1fae5;" dir="ltr">
                            <i class="fa-regular fa-calendar"></i> {{ $released }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Tech stack ===== --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($stack as $group)
            <x-filament::section>
                <x-slot name="heading">
                    <span style="display:inline-flex;align-items:center;gap:.6rem;">
                        <span style="display:inline-flex;height:2rem;width:2rem;align-items:center;justify-content:center;border-radius:.55rem;background:{{ $group['color'] }}1a;">
                            <i class="{{ $group['icon'] }}" style="color:{{ $group['color'] }};font-size:.9rem;"></i>
                        </span>
                        {{ $group['label'] }}
                    </span>
                </x-slot>

                <div style="display:flex;flex-direction:column;gap:.1rem;">
                    @foreach ($group['items'] as $item)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.55rem .25rem;border-bottom:1px solid rgba(120,120,120,.12);">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['name'] }}</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-100" dir="ltr" style="text-align:end;">{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- ===== Developer ===== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="display:inline-flex;align-items:center;gap:.6rem;">
                <span style="display:inline-flex;height:2rem;width:2rem;align-items:center;justify-content:center;border-radius:.55rem;background:#006C351a;">
                    <i class="fa-solid fa-user-tie" style="color:#006C35;font-size:.9rem;"></i>
                </span>
                {{ __('about.group.developer') }}
            </span>
        </x-slot>

        <div style="display:flex;align-items:center;gap:1.1rem;flex-wrap:wrap;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:1rem;">
                <span style="display:flex;height:3.2rem;width:3.2rem;align-items:center;justify-content:center;border-radius:9999px;background:linear-gradient(135deg,#006C35,#C9A961);color:#fff;font-weight:800;font-size:1.1rem;">
                    <i class="fa-solid fa-user"></i>
                </span>
                <div>
                    <div class="text-base font-extrabold text-gray-900 dark:text-white">{{ $developer['name'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ $developer['name_en'] }} · {{ __('about.role') }}</div>
                </div>
            </div>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <a href="{{ $developer['website'] }}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:.45rem;border-radius:.6rem;background:#006C35;color:#fff;padding:.5rem .9rem;font-size:.8rem;font-weight:700;text-decoration:none;" dir="ltr">
                    <i class="fa-solid fa-globe"></i> {{ preg_replace('#^https?://#', '', $developer['website']) }}
                </a>
                <a href="https://wa.me/966{{ ltrim($developer['phone'], '0') }}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:.45rem;border-radius:.6rem;background:#25D366;color:#fff;padding:.5rem .9rem;font-size:.8rem;font-weight:700;text-decoration:none;" dir="ltr">
                    <i class="fa-brands fa-whatsapp"></i> {{ $developer['phone'] }}
                </a>
            </div>
        </div>
    </x-filament::section>

    <p class="text-center text-xs text-gray-400">
        {{ __('about.copyright', ['year' => now()->year, 'site' => $siteName]) }} · {{ __('about.env') }}: {{ $phpEnv }}
    </p>
</x-filament-panels::page>
