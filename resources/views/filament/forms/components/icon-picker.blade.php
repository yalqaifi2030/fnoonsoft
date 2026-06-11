<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            search: '',
            icons: @js(\App\Support\Icons::list()),
            get filtered() {
                if (! this.search) return this.icons;
                const q = this.search.toLowerCase().replace(/\s+/g, '');
                return this.icons.filter((i) => i.replace('fa-solid fa-', '').toLowerCase().includes(q));
            },
        }"
        class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900"
    >
        {{-- Selected preview + search --}}
        <div class="mb-3 flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-lg text-primary-600 dark:bg-primary-500/10">
                <template x-if="state"><i :class="state"></i></template>
                <template x-if="! state"><i class="fa-regular fa-face-smile text-gray-300"></i></template>
            </span>

            <input
                x-model="search"
                type="text"
                placeholder="{{ __('category.icon_search') }}"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
            />

            <button type="button" x-show="state" x-cloak x-on:click="state = null"
                    class="shrink-0 rounded-lg px-2 py-2 text-gray-400 transition hover:text-red-500"
                    title="{{ __('category.icon_clear') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Icon grid --}}
        <div class="grid max-h-56 grid-cols-7 gap-1.5 overflow-y-auto sm:grid-cols-10" style="scrollbar-width: thin;">
            <template x-for="icon in filtered" :key="icon">
                <button
                    type="button"
                    x-on:click="state = icon"
                    :title="icon.replace('fa-solid fa-', '')"
                    :class="state === icon
                        ? 'bg-primary-600 text-white ring-2 ring-primary-600'
                        : 'text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10'"
                    class="flex aspect-square items-center justify-center rounded-lg text-base transition"
                >
                    <i :class="icon"></i>
                </button>
            </template>
        </div>

        <p x-show="filtered.length === 0" class="py-6 text-center text-sm text-gray-400">
            {{ __('category.icon_none') }}
        </p>
    </div>
</x-dynamic-component>
