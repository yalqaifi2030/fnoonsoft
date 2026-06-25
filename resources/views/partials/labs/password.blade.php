{{-- Password & hashing lab: strength, entropy, crack time, live SHA-256 --}}
<div x-data="labPassword()" class="space-y-5">
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">مختبر كلمات المرور</h3>
        <p class="mt-1 text-sm text-gray-500">اكتب كلمة مرور لتحليلها حيًّا (لا تُرسَل لأيّ خادم — كلّ الحساب في متصفّحك).</p>

        <div class="mt-4 flex items-center gap-2" dir="ltr">
            <input :type="show ? 'text' : 'password'" x-model="pw" @input="hash()" placeholder="••••••••••"
                   class="flex-1 font-mono" autocomplete="off">
            <button @click="show = !show" class="rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-500" :title="show ? 'إخفاء' : 'إظهار'">
                <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        </div>

        {{-- strength bar --}}
        <div class="mt-4">
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full transition-all duration-300" :style="'width:' + strength.p + '%; background:' + strength.c"></div>
            </div>
            <div class="mt-1 flex justify-between text-xs">
                <span class="font-bold" :style="'color:' + strength.c" x-text="pw ? strength.t : '—'"></span>
                <span class="text-gray-400"><span x-text="entropy"></span> بت إنتروبيا</span>
            </div>
        </div>

        {{-- character classes --}}
        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4" dir="ltr">
            <template x-for="cc in classes" :key="cc.k">
                <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold transition"
                     :class="cc.ok ? 'border-saudi-green/30 bg-saudi-green/5 text-saudi-green' : 'border-gray-100 text-gray-400'">
                    <i class="fa-solid" :class="cc.ok ? 'fa-circle-check' : 'fa-circle'"></i> <span x-text="cc.label"></span>
                </div>
            </template>
        </div>

        {{-- crack time --}}
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-gray-50 p-4 text-center">
                <div class="text-lg font-black text-luxury-black" dir="ltr" x-text="pw.length"></div>
                <div class="text-xs text-gray-500">عدد المحارف</div>
            </div>
            <div class="rounded-2xl bg-rose-50 p-4 text-center">
                <div class="text-lg font-black text-rose-600" dir="ltr" x-text="crack"></div>
                <div class="text-xs text-gray-500">زمن الاختراق المقدّر (10¹⁰ محاولة/ث)</div>
            </div>
        </div>
    </div>

    {{-- SHA-256 --}}
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-base font-black"><i class="fa-solid fa-fingerprint text-saudi-green"></i> بصمة SHA-256</h3>
        <p class="mt-1 text-sm text-gray-500">هكذا تُخزَّن كلمات المرور بأمان: تجزئة لا يمكن عكسها.</p>
        <div class="mt-3 break-all rounded-xl bg-luxury-black p-4 font-mono text-xs leading-relaxed text-emerald-300" dir="ltr"
             x-text="sha || '—'"></div>
    </div>
</div>

@push('scripts')
    <script>
        function labPassword() {
            return {
                pw: '', sha: '', show: false,
                get charset() {
                    let s = 0;
                    if (/[a-z]/.test(this.pw)) s += 26;
                    if (/[A-Z]/.test(this.pw)) s += 26;
                    if (/[0-9]/.test(this.pw)) s += 10;
                    if (/[^a-zA-Z0-9]/.test(this.pw)) s += 33;
                    return s;
                },
                get classes() {
                    return [
                        { k: 'l', label: 'حروف صغيرة', ok: /[a-z]/.test(this.pw) },
                        { k: 'u', label: 'حروف كبيرة', ok: /[A-Z]/.test(this.pw) },
                        { k: 'd', label: 'أرقام', ok: /[0-9]/.test(this.pw) },
                        { k: 's', label: 'رموز', ok: /[^a-zA-Z0-9]/.test(this.pw) },
                    ];
                },
                get entropy() { return this.pw ? Math.round(this.pw.length * Math.log2(this.charset || 1)) : 0; },
                get strength() {
                    const e = this.entropy;
                    if (!this.pw) return { t: '—', c: '#9ca3af', p: 0 };
                    if (e < 28) return { t: 'ضعيفة جدًّا', c: '#ef4444', p: 15 };
                    if (e < 36) return { t: 'ضعيفة', c: '#f97316', p: 35 };
                    if (e < 60) return { t: 'متوسّطة', c: '#eab308', p: 60 };
                    if (e < 128) return { t: 'قوية', c: '#22c55e', p: 85 };
                    return { t: 'قوية جدًّا', c: '#16a34a', p: 100 };
                },
                get crack() {
                    if (!this.pw) return '—';
                    const combos = Math.pow(this.charset || 1, this.pw.length);
                    return this.human(combos / 2 / 1e10);
                },
                human(s) {
                    if (s < 1) return 'فوري';
                    const units = [['قرن', 3153600000], ['سنة', 31536000], ['يوم', 86400], ['ساعة', 3600], ['دقيقة', 60], ['ثانية', 1]];
                    for (const [n, v] of units) {
                        if (s >= v) {
                            const x = s / v;
                            if (x > 1e12) return 'تريليونات ' + n;
                            if (x > 1e9) return 'مليارات ' + n;
                            if (x > 1e6) return 'ملايين ' + n;
                            return Math.round(x).toLocaleString('en') + ' ' + n;
                        }
                    }
                    return 'فوري';
                },
                async hash() {
                    if (!this.pw) { this.sha = ''; return; }
                    try {
                        const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(this.pw));
                        this.sha = Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
                    } catch (e) { this.sha = '(SHA-256 يتطلّب اتصالًا آمنًا HTTPS)'; }
                },
            };
        }
    </script>
@endpush
