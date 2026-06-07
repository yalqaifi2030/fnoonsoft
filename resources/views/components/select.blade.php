@props([
    'name',
    'options' => [],      // ['value' => 'Label', ...]
    'value' => null,
    'placeholder' => null,
    'submitOnChange' => false,
    'icon' => null,
    'searchable' => false,
])

@php
    $current = (string) ($value ?? '');
    $initialLabel = $options[$current] ?? $placeholder ?? '';
@endphp

{{--
    Professional custom select used site-wide. Renders a hidden <input> so it
    behaves exactly like a native field inside any form, with a styled,
    RTL-aware Alpine dropdown on top. See fnoonSelect() in layouts/app.
--}}
<div
    x-data="fnoonSelect({
        value: @js($current),
        options: @js($options),
        placeholder: @js($placeholder),
        submitOnChange: @js((bool) $submitOnChange),
        searchable: @js((bool) $searchable),
    })"
    class="relative"
    @keydown.escape.stop="open = false"
    @click.outside="open = false"
>
    <input type="hidden" name="{{ $name }}" :value="value" x-ref="input">

    {{-- trigger --}}
    <button type="button" @click="toggle()"
        class="w-full flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-start transition hover:border-saudi-green/50 focus:outline-none"
        :class="{ 'border-saudi-green ring-2 ring-saudi-green/20': open }">
        <span class="flex items-center gap-2 truncate min-w-0">
            @if ($icon)
                <i class="{{ $icon }} text-saudi-green/70 shrink-0"></i>
            @endif
            <span class="truncate" :class="{ 'text-gray-400': value === '' }" x-text="label">{{ $initialLabel }}</span>
        </span>
        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform shrink-0" :class="{ 'rotate-180': open }"></i>
    </button>

    {{-- dropdown --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute z-40 mt-2 w-full rounded-xl bg-white shadow-2xl border border-royal-gold/15 overflow-hidden">

        <template x-if="searchable">
            <div class="p-2 border-b border-gray-100">
                <input x-model="search" type="text" placeholder="…"
                       class="w-full rounded-lg border-gray-200 text-sm py-1.5 focus:border-saudi-green focus:ring-saudi-green"
                       @click.stop>
            </div>
        </template>

        <ul class="max-h-64 overflow-y-auto py-1">
            <template x-for="opt in filtered" :key="opt.value">
                <li>
                    <button type="button" @click="choose(opt.value)"
                        class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-sm text-start transition hover:bg-saudi-green/10"
                        :class="{ 'bg-saudi-green/5 text-saudi-green font-semibold': opt.value === value }">
                        <span class="truncate" x-text="opt.label"></span>
                        <i class="fa-solid fa-check text-saudi-green text-xs shrink-0" x-show="opt.value === value"></i>
                    </button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">—</li>
        </ul>
    </div>
</div>
