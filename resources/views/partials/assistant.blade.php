@php
    $asstWelcome = \App\Models\Setting::get('assistant_welcome') ?: __('assistant.widget.welcome_default');
    $asstSuggestions = collect(preg_split('/\r\n|\r|\n/', (string) \App\Models\Setting::get('assistant_suggestions', '')))
        ->map(fn ($s) => trim($s))->filter()->take(4)->values();
@endphp

<div x-data="fnoonAssistant({
        endpoint: '{{ route('assistant.chat') }}',
        welcome: @js($asstWelcome),
        suggestions: @js($asstSuggestions),
    })" x-cloak class="fixed bottom-5 end-5 z-[60]" style="inset-inline-end:1.25rem;" dir="{{ $dir }}">

    {{-- Chat panel --}}
    <div x-show="open" x-transition.origin.bottom
         class="mb-3 flex w-[calc(100vw-2.5rem)] max-w-[380px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
         style="height:min(72vh,560px);">
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 text-white" style="background:linear-gradient(135deg,#006C35,#00582b);">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-full" style="background:rgba(201,169,97,.25);">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </span>
                <div class="leading-tight">
                    <div class="text-sm font-extrabold">{{ __('assistant.widget.title') }}</div>
                    <div class="text-[11px] opacity-80">{{ __('assistant.widget.subtitle') }}</div>
                </div>
            </div>
            <button type="button" @click="open=false" class="grid h-8 w-8 place-items-center rounded-full transition hover:bg-white/15" aria-label="{{ __('assistant.widget.close') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Messages --}}
        <div class="flex-1 space-y-3 overflow-y-auto bg-[#FBFAF6] p-3" x-ref="scroll">
            {{-- Welcome bubble --}}
            <div class="flex justify-start">
                <div class="max-w-[88%] rounded-2xl rounded-bl-sm bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm" x-text="welcome"></div>
            </div>

            {{-- Suggested questions (only before the first message) --}}
            <template x-if="messages.length === 0 && suggestions.length">
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <template x-for="(q, i) in suggestions" :key="i">
                        <button type="button" @click="ask(q)"
                                class="rounded-full border border-royal-gold/40 bg-white px-3 py-1.5 text-xs font-semibold text-bronze transition hover:bg-royal-gold/10"
                                x-text="q"></button>
                    </template>
                </div>
            </template>

            {{-- Conversation --}}
            <template x-for="(m, i) in messages" :key="i">
                <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <template x-if="m.role === 'user'">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm px-3.5 py-2 text-sm text-white" style="background:#006C35;" x-text="m.content"></div>
                    </template>
                    <template x-if="m.role === 'assistant'">
                        <div class="max-w-[90%] space-y-2">
                            <div class="whitespace-pre-wrap rounded-2xl rounded-bl-sm bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm" x-text="m.content"></div>
                            <template x-if="m.recommendations && m.recommendations.length">
                                <div class="space-y-1.5">
                                    <template x-for="(rec, ri) in m.recommendations" :key="ri">
                                        <a :href="rec.url" class="flex items-center gap-2.5 rounded-xl border border-royal-gold/20 bg-white px-2.5 py-2 transition hover:border-saudi-green/40 hover:shadow-sm">
                                            <template x-if="rec.icon">
                                                <img :src="rec.icon" alt="" class="h-9 w-9 rounded-lg object-contain">
                                            </template>
                                            <template x-if="!rec.icon">
                                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-gray-100 text-gray-400"><i class="fa-solid fa-cube"></i></span>
                                            </template>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-xs font-bold text-gray-900" x-text="rec.name"></span>
                                                <span class="block truncate text-[11px] text-gray-400" x-text="rec.type"></span>
                                            </span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-300"></i>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="busy" class="flex justify-start">
                <div class="rounded-2xl rounded-bl-sm bg-white px-3.5 py-2.5 text-sm text-gray-400 shadow-sm">
                    <span class="inline-flex gap-1">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-300" style="animation-delay:0ms"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-300" style="animation-delay:120ms"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-300" style="animation-delay:240ms"></span>
                    </span>
                </div>
            </div>

            <p x-show="error" x-text="error" class="text-center text-xs text-red-500"></p>
        </div>

        {{-- Composer --}}
        <form @submit.prevent="send()" class="flex items-center gap-2 border-t border-gray-100 bg-white p-2.5">
            <input type="text" x-model="input" :disabled="busy" maxlength="1000"
                   placeholder="{{ __('assistant.widget.placeholder') }}"
                   class="w-full rounded-full border border-gray-200 px-3.5 py-2 text-sm focus:border-saudi-green focus:outline-none focus:ring-1 focus:ring-saudi-green">
            <button type="submit" :disabled="busy || !input.trim()"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-white transition disabled:opacity-40"
                    style="background:#006C35;" aria-label="{{ __('assistant.widget.send') }}">
                <i class="fa-solid fa-paper-plane text-sm rtl:-scale-x-100"></i>
            </button>
        </form>
    </div>

    {{-- Floating bubble --}}
    <button type="button" @click="toggle()"
            class="group relative ms-auto flex h-14 w-14 items-center justify-center rounded-full text-white shadow-xl transition hover:scale-105"
            style="background:linear-gradient(135deg,#006C35,#00582b);box-shadow:0 14px 30px -10px rgba(0,108,53,.6);"
            aria-label="{{ __('assistant.widget.title') }}">
        <span class="absolute inset-0 rounded-full" style="background:rgba(201,169,97,.35);" x-show="!open"
              x-transition.opacity x-init="$el.classList.add('animate-ping')"></span>
        <i class="fa-solid text-xl" :class="open ? 'fa-xmark' : 'fa-wand-magic-sparkles'"></i>
    </button>
</div>

@push('scripts')
<script>
    function fnoonAssistant(config) {
        return {
            open: false,
            input: '',
            busy: false,
            error: '',
            welcome: config.welcome,
            suggestions: config.suggestions || [],
            messages: [],
            toggle() {
                this.open = !this.open;
                if (this.open) this.$nextTick(() => this.scrollDown());
            },
            ask(q) {
                this.input = q;
                this.send();
            },
            scrollDown() {
                const el = this.$refs.scroll;
                if (el) el.scrollTop = el.scrollHeight;
            },
            async send() {
                const text = this.input.trim();
                if (!text || this.busy) return;
                this.error = '';
                this.messages.push({ role: 'user', content: text });
                this.input = '';
                this.busy = true;
                this.$nextTick(() => this.scrollDown());

                const payload = this.messages.map(m => ({ role: m.role, content: m.content }));

                try {
                    const res = await fetch(config.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ messages: payload }),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.messages.push({ role: 'assistant', content: data.reply || @js(__('assistant.error')), recommendations: [] });
                    } else {
                        this.messages.push({
                            role: 'assistant',
                            content: data.reply,
                            recommendations: data.recommendations || [],
                        });
                    }
                } catch (e) {
                    this.error = @js(__('assistant.error'));
                } finally {
                    this.busy = false;
                    this.$nextTick(() => this.scrollDown());
                }
            },
        };
    }
</script>
@endpush
