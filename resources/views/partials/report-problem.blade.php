{{-- Public "report a problem" widget: floating button + modal with one-click
     page screenshot (html2canvas), optional manual upload, auto context. Opens
     anywhere via window.fnoonReport({software, softwareSlug, error, source}). --}}
<div x-data="reportProblem()"
     x-cloak
     data-report-ignore
     @open-report.window="open($event.detail || {})">

    {{-- Floating launcher (hidden while the modal is open) --}}
    <button type="button" x-show="!show" @click="open({ source: 'web' })"
            class="fixed bottom-5 left-5 z-40 inline-flex items-center gap-2 rounded-full bg-saudi-green px-4 py-2.5 text-sm font-bold text-white shadow-lg ring-1 ring-black/10 transition hover:brightness-110"
            style="background:#006C35">
        <i class="fa-solid fa-bug"></i>
        <span class="hidden sm:inline">{{ __('report.button') }}</span>
    </button>

    {{-- Modal --}}
    <div x-show="show" x-transition.opacity class="fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4" style="display:none">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close()"></div>

        <div class="relative w-full max-w-lg overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
             @keydown.escape.window="close()">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <h3 class="flex items-center gap-2 font-bold text-gray-800">
                    <i class="fa-solid fa-bug text-saudi-green"></i> {{ __('report.title') }}
                </h3>
                <button type="button" @click="close()" class="text-gray-400 transition hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            {{-- Success state --}}
            <template x-if="sent">
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-600">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <p class="text-lg font-bold text-gray-800">{{ __('report.sent_title') }}</p>
                    <p class="mt-1 text-sm text-gray-500" x-text="sentNote"></p>
                    <button type="button" @click="close()" class="mt-5 rounded-lg bg-saudi-green px-5 py-2 text-sm font-bold text-white" style="background:#006C35">{{ __('report.close') }}</button>
                </div>
            </template>

            {{-- Form --}}
            <div x-show="!sent" class="max-h-[72vh] overflow-y-auto px-5 py-4">
                <p class="mb-3 text-xs leading-relaxed text-gray-500">{{ __('report.intro') }}</p>

                {{-- Description --}}
                <label class="mb-1 block text-sm font-semibold text-gray-700">{{ __('report.desc_label') }}</label>
                <textarea x-model="desc" rows="3" maxlength="5000"
                          placeholder="{{ __('report.desc_ph') }}"
                          class="w-full resize-y rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/30"></textarea>

                @guest
                {{-- Guest contact email --}}
                <label class="mb-1 mt-3 block text-sm font-semibold text-gray-700">{{ __('report.email_label') }}</label>
                <input type="email" x-model="email" dir="ltr" placeholder="you@example.com"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-saudi-green focus:ring-2 focus:ring-saudi-green/30">
                @endguest

                {{-- Screenshot --}}
                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700">{{ __('report.shot_label') }}</span>
                        <button type="button" @click="capture()" :disabled="capturing"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-saudi-green/40 px-2.5 py-1 text-xs font-bold text-saudi-green transition hover:bg-saudi-green/5 disabled:opacity-50">
                            <i class="fa-solid" :class="capturing ? 'fa-spinner fa-spin' : 'fa-camera'"></i>
                            <span x-text="shot ? '{{ __('report.recapture') }}' : '{{ __('report.capture') }}'"></span>
                        </button>
                    </div>

                    <template x-if="shot">
                        <div class="relative overflow-hidden rounded-lg border border-gray-200">
                            <img :src="shot" alt="" class="max-h-44 w-full object-contain bg-gray-50">
                            <button type="button" @click="shot=null"
                                    class="absolute top-1.5 end-1.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                    <template x-if="!shot">
                        <p class="rounded-lg border border-dashed border-gray-300 px-3 py-3 text-center text-xs text-gray-400">{{ __('report.shot_hint') }}</p>
                    </template>
                </div>

                {{-- Manual upload --}}
                <div class="mt-3">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-500 hover:text-saudi-green">
                        <i class="fa-solid fa-paperclip"></i>
                        <span x-text="manualName || '{{ __('report.attach') }}'"></span>
                        <input type="file" accept="image/*" class="hidden" @change="pickFile($event)">
                    </label>
                </div>

                {{-- Honeypot --}}
                <input type="text" x-model="hp" tabindex="-1" autocomplete="off"
                       class="absolute -left-[9999px] h-0 w-0 opacity-0" aria-hidden="true">

                <p x-show="errorMsg" x-text="errorMsg" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600"></p>

                <button type="button" @click="submit()" :disabled="sending"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-saudi-green py-2.5 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
                        style="background:#006C35">
                    <i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                    <span x-text="sending ? '{{ __('report.sending') }}' : '{{ __('report.submit') }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.fnoonReport = function (ctx) {
        window.dispatchEvent(new CustomEvent('open-report', { detail: ctx || {} }));
    };

    function reportProblem() {
        return {
            show: false, sending: false, sent: false, capturing: false,
            desc: '', email: '', shot: null, manualFile: null, manualName: '', hp: '',
            errorMsg: '', sentNote: '', ctx: {},

            open(ctx) {
                this.ctx = ctx || {};
                this.errorMsg = '';
                if (!this.sent) { this.desc = this.desc; }
                this.sent = false;
                this.show = true;
            },
            close() { this.show = false; },

            pickFile(e) {
                const f = e.target.files && e.target.files[0];
                if (!f) return;
                this.manualFile = f; this.manualName = f.name;
            },

            loadLib() {
                return new Promise((resolve, reject) => {
                    if (window.html2canvas) return resolve();
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    s.onload = resolve; s.onerror = reject;
                    document.head.appendChild(s);
                });
            },

            async capture() {
                this.errorMsg = ''; this.capturing = true;
                try {
                    await this.loadLib();
                    const canvas = await html2canvas(document.body, {
                        x: window.scrollX, y: window.scrollY,
                        width: window.innerWidth, height: window.innerHeight,
                        useCORS: true, backgroundColor: '#ffffff', logging: false, scale: 1,
                        ignoreElements: (el) => el.hasAttribute && el.hasAttribute('data-report-ignore'),
                    });
                    this.shot = canvas.toDataURL('image/jpeg', 0.82);
                } catch (e) {
                    this.errorMsg = @json(__('report.capture_failed'));
                } finally {
                    this.capturing = false;
                }
            },

            dataURLtoBlob(dataURL) {
                const [head, body] = dataURL.split(',');
                const mime = head.match(/:(.*?);/)[1];
                const bin = atob(body); const len = bin.length; const arr = new Uint8Array(len);
                for (let i = 0; i < len; i++) arr[i] = bin.charCodeAt(i);
                return new Blob([arr], { type: mime });
            },

            async submit() {
                if (this.sending) return;
                if (!this.desc.trim() && !this.shot && !this.manualFile) {
                    this.errorMsg = @json(__('report.need_info'));
                    return;
                }
                this.sending = true; this.errorMsg = '';

                const fd = new FormData();
                fd.append('description', this.desc);
                if (this.email) fd.append('email', this.email);
                fd.append('url', location.href);
                if (this.ctx.software) fd.append('software', this.ctx.software);
                if (this.ctx.softwareSlug) fd.append('software_slug', this.ctx.softwareSlug);
                if (this.ctx.error) fd.append('error', this.ctx.error);
                fd.append('source', this.ctx.source || 'web');
                fd.append('browser', navigator.userAgent);
                fd.append('os', navigator.platform || '');
                fd.append('screen', window.innerWidth + 'x' + window.innerHeight);
                fd.append('website', this.hp);
                if (this.shot) fd.append('screenshot', this.dataURLtoBlob(this.shot), 'screenshot.jpg');
                if (this.manualFile) fd.append('attachment', this.manualFile);

                try {
                    const res = await fetch(@json(route('problem.report')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const j = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(j.message || @json(__('report.failed')));
                    this.sentNote = j.ticket ? (@json(__('report.sent_ref')) + ' ' + j.ticket) : '';
                    this.sent = true;
                    this.desc = ''; this.shot = null; this.manualFile = null; this.manualName = '';
                } catch (e) {
                    this.errorMsg = e.message || @json(__('report.failed'));
                } finally {
                    this.sending = false;
                }
            },
        };
    }
</script>
@endpush
