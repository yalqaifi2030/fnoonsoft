<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    @php($user = $this->getUser())
    @php($enabled = $user?->hasTwoFactorEnabled())

    <div class="mx-auto w-full max-w-2xl space-y-6">

        {{-- ===== Recovery codes (shown once) ===== --}}
        @if (! empty($recoveryCodes))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                <h3 class="mb-1 flex items-center gap-2 font-bold text-amber-800 dark:text-amber-200">
                    <x-heroicon-o-key class="h-5 w-5" /> {{ __('security.recovery_title') }}
                </h3>
                <p class="mb-3 text-sm text-amber-700 dark:text-amber-300/80">{{ __('security.recovery_hint') }}</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" dir="ltr">
                    @foreach ($recoveryCodes as $code)
                        <code class="rounded-lg bg-white px-2 py-1.5 text-center font-mono text-sm text-gray-800 ring-1 ring-amber-200 dark:bg-gray-900 dark:text-gray-100">{{ $code }}</code>
                    @endforeach
                </div>
                <div class="mt-3" x-data>
                    <x-filament::button color="gray" size="sm"
                        x-on:click="window.fnoonCopy(@js(implode(PHP_EOL, $recoveryCodes))); $tooltip(@js(__('security.copied')))">
                        <x-heroicon-m-clipboard-document class="me-1 h-4 w-4" /> {{ __('security.copy_codes') }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- ===== Two-factor card ===== --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl"
                      style="background: {{ $enabled ? '#dcfce7' : '#f1f5f9' }}; color: {{ $enabled ? '#16a34a' : '#64748b' }}">
                    <x-heroicon-o-shield-check class="h-7 w-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-bold text-gray-900 dark:text-white">{{ __('security.2fa_title') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('security.2fa_subtitle') }}</p>

                    @if ($enabled)
                        <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-500/15 dark:text-green-300">
                            <x-heroicon-m-check-circle class="h-4 w-4" /> {{ __('security.on') }}
                        </span>
                    @else
                        <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500 dark:bg-white/10">
                            {{ __('security.off') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="mt-5 border-t border-gray-100 pt-5 dark:border-white/5">

                {{-- (A) Setup in progress: QR + confirm --}}
                @if ($showingSetup && $otpauthUri)
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ __('security.setup_steps') }}</p>

                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                        <div wire:ignore
                             x-data="{ render() { if (window.QRCode) { this.$refs.qr.innerHTML = ''; new QRCode(this.$refs.qr, { text: @js($otpauthUri), width: 184, height: 184, colorDark: '#0f172a', colorLight: '#ffffff' }); } } }"
                             x-init="$nextTick(() => render())">
                            <div x-ref="qr" class="inline-block rounded-xl bg-white p-2 ring-1 ring-gray-200"></div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-500">{{ __('security.manual_key') }}</p>
                            <code class="mt-1 block break-all rounded-lg bg-gray-50 px-3 py-2 font-mono text-sm text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200" dir="ltr">{{ $secret }}</code>

                            <label class="mt-4 block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('security.enter_code') }}</label>
                            <input type="text" wire:model="confirmCode" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" dir="ltr"
                                   wire:keydown.enter="confirmEnable"
                                   class="mt-1 w-full rounded-xl border-gray-300 text-center font-mono text-lg tracking-[0.4em] focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800">
                            @error('confirmCode') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror

                            <div class="mt-4 flex gap-2">
                                <x-filament::button wire:click="confirmEnable" wire:loading.attr="disabled">
                                    {{ __('security.confirm_enable') }}
                                </x-filament::button>
                                <x-filament::button color="gray" wire:click="cancelEnable">{{ __('security.cancel') }}</x-filament::button>
                            </div>
                        </div>
                    </div>

                {{-- (B) Already enabled: disable + regenerate --}}
                @elseif ($enabled)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('security.disable_label') }}</label>
                            <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                                <input type="password" wire:model="disablePassword" placeholder="{{ __('security.password') }}"
                                       class="w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800">
                                <x-filament::button color="danger" wire:click="disable" wire:loading.attr="disabled" class="shrink-0">
                                    {{ __('security.disable') }}
                                </x-filament::button>
                            </div>
                            @error('disablePassword') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="border-t border-gray-100 pt-4 dark:border-white/5">
                            <p class="mb-2 text-sm text-gray-500">{{ __('security.recovery_regenerate_hint') }}</p>
                            <x-filament::button color="gray" size="sm" wire:click="regenerateRecoveryCodes"
                                                wire:confirm="{{ __('security.recovery_regenerate_confirm') }}">
                                <x-heroicon-m-arrow-path class="me-1 h-4 w-4" /> {{ __('security.recovery_regenerate') }}
                            </x-filament::button>
                        </div>
                    </div>

                {{-- (C) Off: enable --}}
                @else
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ __('security.enable_hint') }}</p>
                    <x-filament::button wire:click="startEnable" wire:loading.attr="disabled">
                        <x-heroicon-m-shield-check class="me-1 h-5 w-5" /> {{ __('security.enable') }}
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
