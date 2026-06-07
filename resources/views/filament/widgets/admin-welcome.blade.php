<x-filament-widgets::widget>
    <div style="background: linear-gradient(120deg, #006C35 0%, #00582b 55%, #0f1419 100%);"
         class="relative overflow-hidden rounded-2xl text-white">

        <div class="absolute inset-0 opacity-40"
             style="background-image:linear-gradient(rgba(201,169,97,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(201,169,97,.08) 1px,transparent 1px);background-size:42px 42px;"></div>
        <div class="absolute -top-16 -end-10 h-56 w-56 rounded-full"
             style="background:radial-gradient(circle, rgba(201,169,97,.25), transparent 70%);"></div>

        <div class="relative flex flex-col gap-5 p-6 md:p-8 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-green-200">{{ __('admin.welcome.hi') }}</p>
                <h2 class="mt-1 text-2xl md:text-3xl font-bold">{{ $name }}</h2>
                <p class="mt-1 text-sm text-green-100/90">{{ __('admin.welcome.text') }}</p>
                @if ($roles)
                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4" style="color:#C9A961;" />
                        {{ $roles }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-8 w-8" style="color:#C9A961;" />
                </span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
