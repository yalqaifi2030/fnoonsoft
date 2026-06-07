<x-filament-widgets::widget>
    <div style="background: linear-gradient(120deg, #006C35 0%, #00582b 60%, #0f1419 100%);"
         class="relative overflow-hidden rounded-2xl text-white">

        {{-- decorative grid + orb --}}
        <div class="absolute inset-0 opacity-40"
             style="background-image:linear-gradient(rgba(201,169,97,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(201,169,97,.08) 1px,transparent 1px);background-size:42px 42px;"></div>
        <div class="absolute -top-16 -end-10 h-56 w-56 rounded-full"
             style="background:radial-gradient(circle, rgba(201,169,97,.25), transparent 70%);"></div>

        <div class="relative flex flex-col gap-6 p-6 md:p-8 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm text-green-200">{{ __('upload.welcome.hi') }}</p>
                <h2 class="mt-1 text-2xl md:text-3xl font-bold">{{ $name }}</h2>
                <p class="mt-2 max-w-lg text-green-100/90 text-sm leading-relaxed">
                    {{ __('upload.welcome.text', ['max' => $maxGb]) }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-m-arrow-path-rounded-square" class="h-4 w-4" />
                        {{ __('upload.guide.resumable') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4" />
                        {{ __('upload.guide.secure') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/15">
                        <x-filament::icon icon="heroicon-m-circle-stack" class="h-4 w-4" />
                        {{ $maxGb }} GB
                    </span>
                </div>
            </div>

            <div class="shrink-0">
                <a href="{{ $uploadUrl }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-base font-bold shadow-lg transition hover:shadow-xl"
                   style="color:#006C35;">
                    <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="h-6 w-6" style="color:#006C35;" />
                    {{ __('upload.welcome.cta') }}
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
