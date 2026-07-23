<x-filament-widgets::widget>
    <div style="background: linear-gradient(120deg, #006C35 0%, #00582b 55%, #0f1419 100%);"
         class="relative overflow-hidden rounded-2xl text-white">

        <div class="absolute inset-0 opacity-40"
             style="background-image:linear-gradient(rgba(201,169,97,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(201,169,97,.08) 1px,transparent 1px);background-size:42px 42px;"></div>
        <div class="absolute -top-16 -end-10 h-56 w-56 rounded-full"
             style="background:radial-gradient(circle, rgba(201,169,97,.25), transparent 70%);"></div>

        <div class="relative" style="padding:1.5rem 1.75rem;">
            {{-- Greeting + date --}}
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <span style="display:flex; height:3.5rem; width:3.5rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:1rem; background:rgba(255,255,255,.1); box-shadow:inset 0 0 0 1px rgba(255,255,255,.15);">
                        <i class="fa-solid fa-gauge-high" style="font-size:1.4rem; color:#C9A961;"></i>
                    </span>
                    <div>
                        <p style="font-size:.8rem; color:#bbf7d0;">{{ __('admin.welcome.hi') }}</p>
                        <h2 style="margin-top:.1rem; font-size:1.6rem; font-weight:800; line-height:1.1;">{{ $name }}</h2>
                        @if ($roles)
                            <span style="margin-top:.4rem; display:inline-flex; align-items:center; gap:.4rem; border-radius:9999px; background:rgba(255,255,255,.1); padding:.2rem .65rem; font-size:.7rem; font-weight:600; box-shadow:inset 0 0 0 1px rgba(255,255,255,.15);">
                                <i class="fa-solid fa-shield-halved" style="color:#C9A961;"></i> {{ $roles }}
                            </span>
                        @endif
                    </div>
                </div>
                <div style="display:inline-flex; align-items:center; gap:.45rem; border-radius:9999px; background:rgba(255,255,255,.08); padding:.4rem .85rem; font-size:.72rem; color:#d1fae5;">
                    <i class="fa-regular fa-calendar"></i> {{ $date }}
                </div>
            </div>

            {{-- Today at a glance --}}
            @if (!empty($kpis))
                <div style="display:flex; gap:.6rem; margin-top:1.3rem; flex-wrap:wrap;">
                    @foreach ($kpis as $k)
                        <div style="display:flex; align-items:center; gap:.7rem; flex:1 1 140px; min-width:140px; border-radius:.9rem; background:rgba(255,255,255,.08); padding:.7rem .9rem; box-shadow:inset 0 0 0 1px rgba(255,255,255,.12);">
                            <span style="display:flex; height:2.2rem; width:2.2rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:.6rem; background:rgba(201,169,97,.18);">
                                <i class="fa-solid {{ $k['icon'] }}" style="color:#fcd34d; font-size:.85rem;"></i>
                            </span>
                            <div>
                                <div style="font-size:1.15rem; font-weight:800; line-height:1;" dir="ltr">{{ $k['value'] }}</div>
                                <div style="font-size:.68rem; color:#bbf7d0; margin-top:.15rem;">{{ $k['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Attention + quick actions --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1.4rem; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                    @forelse ($attention as $a)
                        <a href="{{ $a['url'] }}"
                           style="display:inline-flex; align-items:center; gap:.5rem; border-radius:.7rem; background:rgba(255,255,255,.1); padding:.45rem .8rem; font-size:.78rem; font-weight:600; color:#fff; text-decoration:none; box-shadow:inset 0 0 0 1px rgba(255,255,255,.14);">
                            <i class="fa-solid {{ $a['icon'] }}" style="color:#fcd34d;"></i>
                            <span>{{ $a['label'] }}</span>
                            <span style="display:inline-flex; min-width:1.3rem; justify-content:center; border-radius:9999px; background:#C9A961; color:#1a1205; padding:.05rem .4rem; font-size:.72rem; font-weight:800;" dir="ltr">{{ $a['count'] }}</span>
                        </a>
                    @empty
                        <span style="display:inline-flex; align-items:center; gap:.45rem; font-size:.78rem; color:#bbf7d0;">
                            <i class="fa-solid fa-circle-check" style="color:#86efac;"></i> {{ __('dashboard.all_clear') }}
                        </span>
                    @endforelse
                </div>

                <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                    @foreach ($actions as $ac)
                        <a href="{{ $ac['url'] }}"
                           style="display:inline-flex; align-items:center; gap:.45rem; border-radius:.7rem; background:#C9A961; color:#1a1205; padding:.5rem .9rem; font-size:.78rem; font-weight:700; text-decoration:none;">
                            <i class="fa-solid {{ $ac['icon'] }}"></i> {{ $ac['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
