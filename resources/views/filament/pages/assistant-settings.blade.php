<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        {{-- Settings form --}}
        <div class="xl:col-span-3">
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-m-check">
                        {{ __('settings.save') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Live preview --}}
        <div class="xl:col-span-2">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10">
                            <x-heroicon-o-sparkles class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('assistant.preview.title') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('assistant.preview.catalog', ['count' => $this->getCatalogCount()]) }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex h-[28rem] flex-col">
                    {{-- Messages --}}
                    <div class="flex-1 space-y-3 overflow-y-auto p-4" id="assistant-test-scroll"
                         x-data x-init="$watch('$wire.testMessages', () => $nextTick(() => { $el.scrollTop = $el.scrollHeight }))">
                        @forelse ($testMessages as $m)
                            @if ($m['role'] === 'user')
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-primary-600 px-3.5 py-2 text-sm text-white">
                                        {{ $m['content'] }}
                                    </div>
                                </div>
                            @else
                                <div class="flex justify-start">
                                    <div class="max-w-[90%] space-y-2">
                                        <div class="rounded-2xl rounded-bl-sm bg-gray-100 px-3.5 py-2 text-sm text-gray-800 dark:bg-white/5 dark:text-gray-200 whitespace-pre-wrap">{{ $m['content'] }}</div>
                                        @if (! empty($m['recommendations']))
                                            <div class="space-y-1.5">
                                                @foreach ($m['recommendations'] as $rec)
                                                    <a href="{{ $rec['url'] }}" target="_blank"
                                                       class="flex items-center gap-2.5 rounded-lg border border-gray-150 bg-white px-2.5 py-2 transition hover:border-primary-300 hover:shadow-sm dark:border-white/10 dark:bg-gray-800">
                                                        @if ($rec['icon'])
                                                            <img src="{{ $rec['icon'] }}" alt="" class="h-8 w-8 rounded-md object-contain">
                                                        @else
                                                            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-100 text-gray-400 dark:bg-white/10">
                                                                <x-heroicon-o-cube class="h-4 w-4" />
                                                            </span>
                                                        @endif
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block truncate text-xs font-bold text-gray-900 dark:text-white">{{ $rec['name'] }}</span>
                                                            <span class="block truncate text-[11px] text-gray-400">{{ $rec['type'] }}</span>
                                                        </span>
                                                        <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 shrink-0 text-gray-300" />
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="flex h-full flex-col items-center justify-center text-center text-gray-400">
                                <x-heroicon-o-chat-bubble-left-right class="mb-2 h-8 w-8" />
                                <p class="text-sm">{{ __('assistant.preview.empty') }}</p>
                            </div>
                        @endforelse

                        <div wire:loading wire:target="sendTest" class="flex justify-start">
                            <div class="rounded-2xl rounded-bl-sm bg-gray-100 px-3.5 py-2 text-sm text-gray-400 dark:bg-white/5">
                                {{ __('assistant.preview.thinking') }}
                            </div>
                        </div>
                    </div>

                    {{-- Composer --}}
                    <form wire:submit="sendTest" class="border-t border-gray-100 p-3 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model="testInput" wire:loading.attr="disabled" wire:target="sendTest"
                                   placeholder="{{ __('assistant.preview.placeholder') }}"
                                   class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <x-filament::button type="submit" icon="heroicon-m-paper-airplane" wire:loading.attr="disabled" wire:target="sendTest" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
