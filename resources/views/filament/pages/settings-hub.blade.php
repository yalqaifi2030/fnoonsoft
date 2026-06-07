<x-filament-panels::page>
    <div class="settings-grid">
        @foreach ($this->getCards() as $card)
            <a href="{{ $card['url'] }}" class="settings-card group"
               style="--c: {{ $card['color'] }}; --c-soft: {{ $card['color'] }}24;">
                <span class="settings-card__icon">
                    <i class="{{ $card['icon'] }}"></i>
                </span>
                <div>
                    <h3 class="settings-card__title">{{ $card['title'] }}</h3>
                    <p class="settings-card__desc">{{ $card['desc'] }}</p>
                </div>
                <span class="settings-card__arrow">
                    <i class="fa-solid fa-arrow-left"></i>
                </span>
            </a>
        @endforeach
    </div>

    @push('styles')
        <style>
            .settings-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 1.25rem;
            }
            @media (min-width: 640px)  { .settings-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .settings-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (min-width: 1280px) { .settings-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

            .settings-card {
                position: relative;
                display: flex;
                flex-direction: column;
                gap: 1rem;
                padding: 1.5rem;
                border: 1px solid #eef0f2;
                border-radius: 1.25rem;
                background: #fff;
                text-decoration: none;
                overflow: hidden;
                transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            }
            .dark .settings-card { background: #0b1220; border-color: rgba(255,255,255,.08); }

            /* top accent bar revealed on hover */
            .settings-card::before {
                content: '';
                position: absolute;
                inset: 0 0 auto 0;
                height: 3px;
                background: var(--c);
                transform: scaleX(0);
                transform-origin: var(--c-origin, right);
                transition: transform .25s ease;
            }
            .settings-card:hover {
                transform: translateY(-4px);
                border-color: var(--c-soft);
                box-shadow: 0 20px 38px -22px var(--c);
            }
            .settings-card:hover::before { transform: scaleX(1); }

            .settings-card__icon {
                width: 3.5rem;
                height: 3.5rem;
                border-radius: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.4rem;
                color: var(--c);
                background: var(--c-soft);
                transition: transform .22s ease;
            }
            .settings-card:hover .settings-card__icon { transform: scale(1.08) rotate(-4deg); }

            .settings-card__title {
                font-weight: 800;
                font-size: 1.05rem;
                color: #111827;
                transition: color .2s ease;
            }
            .dark .settings-card__title { color: #fff; }
            .settings-card:hover .settings-card__title { color: var(--c); }

            .settings-card__desc {
                margin-top: .3rem;
                font-size: .8rem;
                line-height: 1.65;
                color: #9ca3af;
            }

            .settings-card__arrow {
                position: absolute;
                bottom: 1.4rem;
                inset-inline-end: 1.4rem;
                color: var(--c);
                opacity: 0;
                transform: translateX(10px);
                transition: opacity .22s ease, transform .22s ease;
            }
            .settings-card:hover .settings-card__arrow { opacity: 1; transform: translateX(0); }
            /* RTL: the arrow points the natural reading direction */
            [dir="rtl"] .settings-card__arrow { transform: translateX(-10px); }
            [dir="rtl"] .settings-card:hover .settings-card__arrow { transform: translateX(0); }
        </style>
    @endpush
</x-filament-panels::page>
