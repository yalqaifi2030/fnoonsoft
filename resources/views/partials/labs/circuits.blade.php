{{-- Electric Circuits lab: Ohm's law + LED resistor + series/parallel --}}
<div x-data="labCircuits()" x-init="calcLed(); calcRR()" class="space-y-5">
    <div class="card-luxury flex gap-1 p-2" dir="ltr">
        <template x-for="t in tabs" :key="t.k">
            <button @click="tab = t.k" class="flex-1 rounded-lg px-3 py-2 text-sm font-bold transition"
                    :class="tab === t.k ? 'bg-saudi-green text-white' : 'text-gray-500 hover:bg-saudi-green/10'" x-text="t.label"></button>
        </template>
    </div>

    {{-- Ohm's law --}}
    <div x-show="tab === 'ohm'" class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">قانون أوم — V = I × R</h3>
        <p class="mt-1 text-sm text-gray-500">أدخل قيمتين واترك الباقي فارغًا، ثمّ اضغط «احسب».</p>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" dir="ltr">
            <label class="block"><span class="text-xs font-bold text-gray-500">الجهد V (فولت)</span>
                <input type="number" step="any" x-model="V" class="mt-1 w-full rounded-xl border-gray-200"></label>
            <label class="block"><span class="text-xs font-bold text-gray-500">التيار I (أمبير)</span>
                <input type="number" step="any" x-model="I" class="mt-1 w-full rounded-xl border-gray-200"></label>
            <label class="block"><span class="text-xs font-bold text-gray-500">المقاومة R (أوم)</span>
                <input type="number" step="any" x-model="R" class="mt-1 w-full rounded-xl border-gray-200"></label>
            <label class="block"><span class="text-xs font-bold text-gray-500">القدرة P (واط)</span>
                <input type="number" step="any" x-model="P" readonly class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50"></label>
        </div>
        <p x-show="ohmErr" x-text="ohmErr" class="mt-2 text-sm font-semibold text-red-600"></p>
        <div class="mt-4 flex gap-2">
            <button @click="calcOhm()" class="btn-primary text-sm"><i class="fa-solid fa-equals"></i> احسب</button>
            <button @click="V=I=R=P=''; ohmErr=''" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-500">مسح</button>
        </div>
    </div>

    {{-- LED resistor --}}
    <div x-show="tab === 'led'" class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">حاسبة مقاومة LED</h3>
        <p class="mt-1 text-sm text-gray-500">R = (جهد المصدر − جهد LED) ÷ التيار</p>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3" dir="ltr">
            <label class="block"><span class="text-xs font-bold text-gray-500">جهد المصدر (فولت)</span>
                <input type="number" step="any" x-model.number="vs" @input="calcLed()" class="mt-1 w-full rounded-xl border-gray-200"></label>
            <label class="block"><span class="text-xs font-bold text-gray-500">جهد LED (فولت)</span>
                <input type="number" step="any" x-model.number="vf" @input="calcLed()" class="mt-1 w-full rounded-xl border-gray-200"></label>
            <label class="block"><span class="text-xs font-bold text-gray-500">تيار LED (ملي أمبير)</span>
                <input type="number" step="any" x-model.number="ma" @input="calcLed()" class="mt-1 w-full rounded-xl border-gray-200"></label>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl bg-saudi-green/5 p-4"><div class="text-2xl font-black text-saudi-green" dir="ltr"><span x-text="ledR"></span> Ω</div><div class="text-xs text-gray-500">القيمة المحسوبة</div></div>
            <div class="rounded-2xl bg-royal-gold/10 p-4"><div class="text-2xl font-black text-bronze" dir="ltr"><span x-text="ledStd"></span> Ω</div><div class="text-xs text-gray-500">أقرب مقاومة قياسية</div></div>
            <div class="rounded-2xl bg-gray-50 p-4"><div class="text-2xl font-black text-gray-700" dir="ltr"><span x-text="ledP"></span> W</div><div class="text-xs text-gray-500">القدرة المبدّدة</div></div>
        </div>
    </div>

    {{-- Series / parallel --}}
    <div x-show="tab === 'rr'" class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">مقاومات على التوالي والتوازي</h3>
        <p class="mt-1 text-sm text-gray-500">اكتب قيم المقاومات (Ω) مفصولة بفاصلة.</p>
        <input type="text" x-model="rlist" @input="calcRR()" dir="ltr" placeholder="100, 220, 330"
               class="mt-3 w-full rounded-xl border-gray-200">
        <div class="mt-4 grid grid-cols-2 gap-3 text-center">
            <div class="rounded-2xl bg-saudi-green/5 p-4"><div class="text-2xl font-black text-saudi-green" dir="ltr"><span x-text="seriesR"></span> Ω</div><div class="text-xs text-gray-500">على التوالي (Series)</div></div>
            <div class="rounded-2xl bg-blue-50 p-4"><div class="text-2xl font-black text-blue-600" dir="ltr"><span x-text="parallelR"></span> Ω</div><div class="text-xs text-gray-500">على التوازي (Parallel)</div></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function labCircuits() {
            return {
                tab: 'ohm',
                tabs: [{ k: 'ohm', label: 'قانون أوم' }, { k: 'led', label: 'مقاومة LED' }, { k: 'rr', label: 'مقاومات' }],
                V: '', I: '', R: '', P: '', ohmErr: '',
                r4(x) { return (isNaN(x) || !isFinite(x)) ? '' : Math.round(x * 1e4) / 1e4; },
                calcOhm() {
                    let v = parseFloat(this.V), i = parseFloat(this.I), r = parseFloat(this.R);
                    const known = [!isNaN(v), !isNaN(i), !isNaN(r)].filter(Boolean).length;
                    this.ohmErr = '';
                    if (known < 2) { this.ohmErr = 'أدخل قيمتين على الأقل.'; return; }
                    if (isNaN(v)) v = i * r;
                    else if (isNaN(i)) i = r !== 0 ? v / r : NaN;
                    else if (isNaN(r)) r = i !== 0 ? v / i : NaN;
                    this.V = this.r4(v); this.I = this.r4(i); this.R = this.r4(r); this.P = this.r4(v * i);
                },
                vs: 5, vf: 2, ma: 20, ledR: 0, ledStd: 0, ledP: 0,
                calcLed() {
                    const i = (this.ma || 0) / 1000;
                    const r = i > 0 ? (this.vs - this.vf) / i : 0;
                    this.ledR = Math.round(r * 100) / 100;
                    const std = [100, 150, 220, 330, 470, 680, 1000, 2200, 4700];
                    this.ledStd = std.find(s => s >= r) || std[std.length - 1];
                    this.ledP = Math.round((this.vs - this.vf) * i * 1000) / 1000;
                },
                rlist: '100, 220, 330', seriesR: 0, parallelR: 0,
                calcRR() {
                    const v = this.rlist.split(/[,\s]+/).map(parseFloat).filter(x => !isNaN(x) && x > 0);
                    if (!v.length) { this.seriesR = 0; this.parallelR = 0; return; }
                    this.seriesR = Math.round(v.reduce((a, b) => a + b, 0) * 100) / 100;
                    this.parallelR = Math.round((1 / v.reduce((a, b) => a + 1 / b, 0)) * 100) / 100;
                },
            };
        }
    </script>
@endpush
