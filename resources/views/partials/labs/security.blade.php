{{-- Cybersecurity lab — interactions (tabs) are fully managed from the admin (lab items).
     Each active item = one interactive tab. Built-in types render coded tools; the
     "custom" type renders admin-authored HTML, so brand-new interactions need no code. --}}
@php
    $items = ($lab->activeItems ?? collect());

    $defaultIcons = [
        'caesar' => 'fa-solid fa-right-left',
        'password' => 'fa-solid fa-key',
        'hash' => 'fa-solid fa-fingerprint',
        'base64' => 'fa-solid fa-code',
        'brute' => 'fa-solid fa-stopwatch',
        'custom' => 'fa-solid fa-wand-magic-sparkles',
    ];

    $tabs = $items->map(fn ($it) => [
        'id' => (string) $it->id,
        'type' => $it->d('type', 'caesar'),
        'name' => $it->title ?: ucfirst($it->d('type', 'caesar')),
        'icon' => $it->d('icon') ?: ($defaultIcons[$it->d('type', 'caesar')] ?? 'fa-solid fa-shield-halved'),
        'sample' => (string) $it->d('sample', ''),
    ])->values();

    $samplesById = $tabs->mapWithKeys(fn ($t) => [$t['id'] => $t['sample']])->all();
    $customItems = $items->filter(fn ($it) => $it->d('type') === 'custom');
@endphp

@if ($tabs->isEmpty())
    <div class="card-luxury p-10 text-center">
        <i class="fa-solid fa-flask text-4xl text-gray-300"></i>
        <p class="mt-3 text-gray-400">{{ __('learn.security.empty') }}</p>
    </div>
@else
<div x-data="securityLab(@js(['tabs' => $tabs->all(), 'samples' => $samplesById]))"
     x-init="$watch('hashInput', () => doHash()); init0()">

    {{-- Tabs (managed from admin) --}}
    <div class="flex items-center gap-1 mb-4 overflow-x-auto" dir="ltr">
        <template x-for="t in tabs" :key="t.id">
            <button @click="selectTab(t)" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition"
                    :class="tab===t.id ? 'bg-saudi-green text-white' : 'text-gray-500 hover:bg-saudi-green/10'">
                <i :class="t.icon"></i> <span x-text="t.name"></span>
            </button>
        </template>
    </div>

    {{-- Caesar --}}
    <div x-show="activeType==='caesar'" x-cloak class="card-luxury p-6 grid md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-semibold mb-2">{{ __('learn.security.plain') }}</label>
            <div class="lab-group" dir="ltr">
                <i class="fa-solid fa-font lab-ico"></i>
                <input x-model="text" class="lab-field has-ico mb-4" dir="ltr" placeholder="Attack at dawn">
            </div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-semibold" dir="ltr">{{ __('learn.security.shift') }}</label>
                <span class="inline-flex h-7 min-w-[2rem] items-center justify-center rounded-lg bg-saudi-green/10 px-2 text-sm font-bold text-saudi-green" x-text="shift"></span>
            </div>
            <input type="range" min="1" max="25" x-model.number="shift" class="w-full accent-saudi-green">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-2">{{ __('learn.security.cipher') }}</label>
            <div class="font-mono text-sm bg-luxury-black text-green-300 rounded-xl p-4 break-all h-28 overflow-auto ring-1 ring-white/5" dir="ltr" x-text="caesar"></div>
        </div>
    </div>

    {{-- Password strength --}}
    <div x-show="activeType==='password'" x-cloak class="card-luxury p-6">
        <label class="block text-sm font-semibold mb-2">{{ __('learn.security.password') }}</label>
        <div class="lab-group mb-4" dir="ltr">
            <i class="fa-solid fa-key lab-ico"></i>
            <input x-model="pw" :type="showPw ? 'text' : 'password'" class="lab-field has-ico has-trail font-mono" dir="ltr" placeholder="Try: P@ssw0rd!">
            <button type="button" @click="showPw=!showPw" class="lab-trail" :aria-label="showPw ? 'Hide' : 'Show'">
                <i class="fa-solid" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>
        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full transition-all" :style="`width:${pwScore*25}%`" :class="['bg-gray-200','bg-red-400','bg-amber-400','bg-lime-500','bg-green-600'][pwScore]"></div></div>
        <div class="mt-2 text-sm font-bold" :class="['text-gray-400','text-red-500','text-amber-500','text-lime-600','text-green-600'][pwScore]" x-text="pw ? ['{{ __('learn.security.weak') }}','{{ __('learn.security.weak') }}','{{ __('learn.security.fair') }}','{{ __('learn.security.good') }}','{{ __('learn.security.strong') }}'][pwScore] : ''"></div>
    </div>

    {{-- SHA-256 hash --}}
    <div x-show="activeType==='hash'" x-cloak class="card-luxury p-6">
        <label class="block text-sm font-semibold mb-2">Text → SHA-256</label>
        <div class="lab-group mb-3" dir="ltr">
            <i class="fa-solid fa-fingerprint lab-ico"></i>
            <input x-model="hashInput" class="lab-field has-ico" dir="ltr" placeholder="hash me">
        </div>
        <div class="relative font-mono text-xs bg-luxury-black text-amber-200 rounded-xl p-4 pe-12 break-all ring-1 ring-white/5" dir="ltr">
            <span x-text="hashOutput"></span>
            <button type="button" @click="window.fnoonCopy(hashOutput)"
                    class="absolute end-2 top-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-amber-200 transition hover:bg-white/20" aria-label="Copy">
                <i class="fa-solid fa-copy text-xs"></i>
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-2">SHA-256 is one-way — the same input always gives the same 64-char digest.</p>
    </div>

    {{-- Base64 --}}
    <div x-show="activeType==='base64'" x-cloak class="card-luxury p-6 grid md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-semibold mb-2"><i class="fa-solid fa-align-left text-saudi-green/60 me-1"></i> Text</label>
            <textarea x-model="b64text" @input="b64enc()" rows="5" class="lab-field font-mono resize-none" dir="ltr" placeholder="Type plain text…"></textarea>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-2"><i class="fa-solid fa-code text-saudi-green/60 me-1"></i> Base64</label>
            <textarea x-model="b64val" @input="b64dec()" rows="5" class="lab-field font-mono resize-none bg-gray-50" dir="ltr" placeholder="SGVsbG8="></textarea>
        </div>
    </div>

    {{-- Brute-force estimator --}}
    <div x-show="activeType==='brute'" x-cloak class="card-luxury p-6">
        <label class="block text-sm font-semibold mb-2">Password</label>
        <div class="lab-group mb-4" dir="ltr">
            <i class="fa-solid fa-stopwatch lab-ico"></i>
            <input x-model="bf" type="text" class="lab-field has-ico font-mono" dir="ltr" placeholder="Type a password">
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Length</div><div class="text-xl font-bold" x-text="bf.length"></div></div>
            <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Charset</div><div class="text-xl font-bold" x-text="bfCharset"></div></div>
            <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-gray-500">Combinations</div><div class="text-sm font-bold font-mono" x-text="bfCombos"></div></div>
            <div class="rounded-xl p-3" :class="bfColor"><div class="text-xs opacity-80">Crack time</div><div class="text-sm font-bold" x-text="bfTime"></div></div>
        </div>
        <p class="text-xs text-gray-400 mt-3">Estimated at 10 billion guesses/second (modern GPU).</p>
    </div>

    {{-- Custom interactions (admin-authored HTML) --}}
    @foreach ($customItems as $ci)
        <div x-show="tab==='{{ $ci->id }}'" x-cloak class="card-luxury p-6">
            @if ($ci->description)
                <p class="mb-4 text-sm text-gray-500">{{ $ci->description }}</p>
            @endif
            <div class="lab-custom" dir="auto">{!! $ci->d('html', '') !!}</div>
        </div>
    @endforeach
</div>
@endif

@push('styles')
<style>
    .lab-group { position: relative; }
    .lab-field {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.65rem 0.9rem;
        font-size: 0.875rem;
        background: #fff;
        color: #1f2937;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .lab-field:focus {
        outline: none;
        border-color: #006C35;
        box-shadow: 0 0 0 3px rgba(0, 108, 53, .15);
    }
    .lab-field::placeholder { color: #cbd5e1; }
    .lab-field.has-ico { padding-left: 2.6rem; }
    .lab-field.has-trail { padding-right: 2.8rem; }
    .lab-ico {
        position: absolute; left: 0.9rem; top: 0.85rem;
        color: #9ca3af; pointer-events: none; font-size: 0.9rem;
    }
    .lab-trail {
        position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%);
        width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center;
        border-radius: 0.5rem; color: #9ca3af; transition: color .15s, background .15s;
    }
    .lab-trail:hover { color: #006C35; background: rgba(0, 108, 53, .08); }
    .lab-custom :where(input, textarea, select) { /* keep admin HTML inputs tidy */
        border: 1px solid #e5e7eb; border-radius: .6rem; padding: .5rem .75rem;
    }
</style>
@endpush

@push('scripts')
<script>
    function securityLab(config) {
        config = config || {};
        const tabs = config.tabs || [];
        const samples = config.samples || {};
        return {
            tabs,
            samples,
            tab: tabs.length ? tabs[0].id : null,
            get activeType() { const t = this.tabs.find((x) => x.id === this.tab); return t ? t.type : null; },
            selectTab(t) { this.tab = t.id; this.loadSample(t); },
            loadSample(t) {
                const s = this.samples[t.id];
                if (s === undefined || s === '') return;
                if (t.type === 'caesar') this.text = s;
                else if (t.type === 'password') this.pw = s;
                else if (t.type === 'hash') { this.hashInput = s; this.doHash(); }
                else if (t.type === 'base64') { this.b64text = s; this.b64enc(); }
                else if (t.type === 'brute') this.bf = s;
            },
            init0() { if (this.tabs.length) this.loadSample(this.tabs[0]); this.doHash(); this.b64enc(); },
            // caesar
            shift: 3, text: 'Attack at dawn',
            get caesar() { const s = ((this.shift % 26) + 26) % 26; return this.text.replace(/[a-z]/gi, c => { const b = c <= 'Z' ? 65 : 97; return String.fromCharCode((c.charCodeAt(0) - b + s) % 26 + b); }); },
            // password
            pw: '', showPw: false,
            get pwScore() { let s = 0; if (this.pw.length >= 8) s++; if (/[A-Z]/.test(this.pw)) s++; if (/[0-9]/.test(this.pw)) s++; if (/[^A-Za-z0-9]/.test(this.pw)) s++; return s; },
            // hash
            hashInput: 'hello', hashOutput: '',
            async doHash() {
                if (!this.hashInput) { this.hashOutput = ''; return; }
                const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(this.hashInput));
                this.hashOutput = [...new Uint8Array(buf)].map(b => b.toString(16).padStart(2, '0')).join('');
            },
            // base64
            b64text: 'Hello Fnoon', b64val: '',
            b64enc() { try { this.b64val = btoa(unescape(encodeURIComponent(this.b64text))); } catch (e) { this.b64val = ''; } },
            b64dec() { try { this.b64text = decodeURIComponent(escape(atob(this.b64val))); } catch (e) {} },
            // brute-force
            bf: '',
            get bfCharset() { let n = 0; if (/[a-z]/.test(this.bf)) n += 26; if (/[A-Z]/.test(this.bf)) n += 26; if (/[0-9]/.test(this.bf)) n += 10; if (/[^A-Za-z0-9]/.test(this.bf)) n += 33; return n; },
            get bfCombos() { if (!this.bf) return '0'; const c = Math.pow(this.bfCharset, this.bf.length); return c.toExponential(2); },
            get _secs() { return this.bf ? Math.pow(this.bfCharset, this.bf.length) / 1e10 : 0; },
            get bfTime() {
                const s = this._secs;
                if (!this.bf) return '—';
                if (s < 1) return 'instant';
                const u = [['year', 31536000], ['day', 86400], ['hour', 3600], ['min', 60], ['sec', 1]];
                for (const [name, sec] of u) { if (s >= sec) { const v = Math.floor(s / sec); return v.toLocaleString() + ' ' + name + (v > 1 ? 's' : ''); } }
                return '< 1 sec';
            },
            get bfColor() { const s = this._secs; if (s < 60) return 'bg-red-100 text-red-700'; if (s < 86400 * 365) return 'bg-amber-100 text-amber-700'; return 'bg-green-100 text-green-700'; },
        };
    }
</script>
@endpush
