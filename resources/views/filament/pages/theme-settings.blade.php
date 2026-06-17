<x-filament-panels::page>
    @php
        $P = $this->data['theme_primary'] ?? '#006C35';
        $S = $this->data['theme_secondary'] ?? '#C9A961';
        $A = $this->data['theme_accent'] ?? '#8B6F47';
    @endphp

    <form wire:submit="save" class="space-y-6">
        {{-- ===== Presets ===== --}}
        <x-filament::section icon="heroicon-o-sparkles">
            <x-slot name="heading">{{ __('theme.section.presets') }}</x-slot>
            <x-slot name="description">{{ __('theme.section.presets_hint') }}</x-slot>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($this->getPresets() as $key => $c)
                    <button type="button" wire:click="applyPreset('{{ $key }}')"
                            class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-2.5 text-start transition hover:border-primary-400 hover:shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <span class="shrink-0" style="display:flex; width:2.25rem; height:2.25rem; border-radius:.5rem; overflow:hidden; box-shadow:0 0 0 1px rgba(0,0,0,.08);">
                            <span style="flex:1 1 0; background: {{ $c[0] }}"></span>
                            <span style="flex:1 1 0; background: {{ $c[1] }}"></span>
                            <span style="flex:1 1 0; background: {{ $c[2] }}"></span>
                        </span>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('theme.preset.'.$key) }}</span>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- ===== Form ===== --}}
            <div class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-m-check">
                        {{ __('settings.save') }}
                    </x-filament::button>
                </div>
            </div>

            {{-- ===== Live preview ===== --}}
            <div class="lg:sticky lg:top-24 self-start w-full">
                <x-filament::section icon="heroicon-o-eye">
                    <x-slot name="heading">{{ __('theme.preview.title') }}</x-slot>
                    <x-slot name="description">{{ __('theme.preview.hint') }}</x-slot>

                    <div class="overflow-hidden rounded-xl ring-1 ring-black/5">
                        {{-- hero band --}}
                        <div class="p-5 text-white"
                             style="background: linear-gradient(135deg, {{ $P }}, color-mix(in srgb, {{ $P }} 55%, #000));">
                            <div class="mb-2 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
                                 style="background: {{ $S }}33; border: 1px solid {{ $S }}55; color: #fff;">
                                <i class="fa-solid fa-star" style="color: {{ $S }}"></i> {{ __('theme.preview.badge') }}
                            </div>
                            <h3 class="font-bold text-xl">{{ __('theme.preview.headline') }}</h3>
                            <p class="text-white/85 text-sm mt-1">{{ __('theme.preview.sub') }}</p>
                        </div>

                        {{-- body --}}
                        <div class="space-y-4 bg-white p-5 dark:bg-gray-900">
                            {{-- buttons --}}
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold text-white"
                                      style="background: linear-gradient(135deg, {{ $P }}, color-mix(in srgb, {{ $P }} 80%, #000));">
                                    <i class="fa-solid fa-download"></i> {{ __('theme.preview.btn_primary') }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold"
                                      style="background: linear-gradient(135deg, {{ $S }}, color-mix(in srgb, {{ $S }} 80%, #000)); color:#1A1A1A;">
                                    <i class="fa-solid fa-crown"></i> {{ __('theme.preview.btn_secondary') }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold"
                                      style="border:2px solid {{ $A }}; color: {{ $A }};">
                                    {{ __('theme.preview.btn_accent') }}
                                </span>
                            </div>

                            {{-- card --}}
                            <div class="rounded-xl p-4" style="border:1px solid {{ $S }}33;">
                                <div class="flex items-center gap-3">
                                    <span class="text-white" style="display:flex; align-items:center; justify-content:center; width:2.5rem; height:2.5rem; border-radius:.5rem; background: {{ $P }}">
                                        <i class="fa-solid fa-cube"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white" style="color: {{ $P }}">{{ __('theme.preview.card_title') }}</div>
                                        <div class="text-xs text-gray-400">{{ __('theme.preview.card_sub') }}</div>
                                    </div>
                                    <span class="ms-auto rounded-full px-2.5 py-1 text-xs font-bold"
                                          style="background: {{ $S }}1f; color: {{ $A }};">{{ __('theme.preview.tag') }}</span>
                                </div>
                            </div>

                            {{-- swatches --}}
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ([[__('theme.primary'), $P], [__('theme.secondary'), $S], [__('theme.accent'), $A]] as [$label, $hex])
                                    <div class="overflow-hidden" style="border-radius:.5rem; box-shadow:0 0 0 1px rgba(0,0,0,.05);">
                                        <div style="height:2.5rem; background: {{ $hex }}"></div>
                                        <div class="px-2 py-1.5 bg-white dark:bg-gray-900">
                                            <div class="text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ $label }}</div>
                                            <div class="text-[11px] text-gray-400" dir="ltr">{{ strtoupper($hex) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </form>
</x-filament-panels::page>
