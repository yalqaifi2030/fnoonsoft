{{-- Number systems & bitwise logic lab --}}
<div x-data="labNumbers()" x-init="convert(); calcBit()" class="space-y-5">

    {{-- Base converter --}}
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">محوّل الأنظمة العددية</h3>
        <div class="mt-4 flex flex-wrap items-end gap-3" dir="ltr">
            <label class="flex-1"><span class="text-xs font-bold text-gray-500">القيمة</span>
                <input type="text" x-model="input" @input="convert()" class="mt-1 w-full rounded-xl border-gray-200 font-mono"></label>
            <div><span class="text-xs font-bold text-gray-500">النظام المُدخَل</span>
                <div class="mt-1 w-40" @fnoon-select-change="base = parseInt($event.detail); convert()">
                    <x-select name="num_base" value="10"
                        :options="['10' => 'عشري (10)', '2' => 'ثنائي (2)', '16' => 'سداسي (16)', '8' => 'ثماني (8)']" />
                </div>
            </div>
        </div>
        <p x-show="convErr" x-text="convErr" class="mt-2 text-sm font-semibold text-red-600"></p>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" dir="ltr">
            <template x-for="row in [['عشري','dec','#006C35'],['ثنائي','bin','#3b82f6'],['سداسي','hex','#8b5cf6'],['ثماني','oct','#b8860b']]" :key="row[1]">
                <div class="rounded-2xl border border-gray-100 p-4">
                    <div class="text-xs font-bold text-gray-400" x-text="row[0]"></div>
                    <div class="mt-1 break-all font-mono text-lg font-black" :style="'color:'+row[2]" x-text="out[row[1]] || '—'"></div>
                </div>
            </template>
        </div>
        <p class="mt-3 text-xs text-gray-400">المثال: <button @click="input='255'; base=10; convert()" class="font-bold text-saudi-green">255</button> ·
            <button @click="input='FF'; base=16; convert()" class="font-bold text-saudi-green">FF</button> ·
            <button @click="input='1010'; base=2; convert()" class="font-bold text-saudi-green">1010₂</button></p>
    </div>

    {{-- Bitwise --}}
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">العمليّات المنطقية (Bitwise)</h3>
        <div class="mt-4 flex flex-wrap items-end gap-3" dir="ltr">
            <label><span class="text-xs font-bold text-gray-500">A</span>
                <input type="number" x-model.number="a" @input="calcBit()" class="mt-1 w-28 rounded-xl border-gray-200 font-mono"></label>
            <div><span class="text-xs font-bold text-gray-500">العملية</span>
                <div class="mt-1 w-40" @fnoon-select-change="op = $event.detail; calcBit()">
                    <x-select name="bit_op" value="AND"
                        :options="['AND' => 'AND (&)', 'OR' => 'OR (|)', 'XOR' => 'XOR (^)', 'NOT' => 'NOT (~A)', 'SHL' => 'A << 1', 'SHR' => 'A >> 1']" />
                </div>
            </div>
            <label x-show="op !== 'NOT' && op !== 'SHL' && op !== 'SHR'"><span class="text-xs font-bold text-gray-500">B</span>
                <input type="number" x-model.number="b" @input="calcBit()" class="mt-1 w-28 rounded-xl border-gray-200 font-mono"></label>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-2xl bg-saudi-green/5 p-4 text-center"><div class="font-mono text-2xl font-black text-saudi-green" dir="ltr" x-text="bitOut"></div><div class="text-xs text-gray-500">النتيجة (عشري)</div></div>
            <div class="rounded-2xl bg-blue-50 p-4 text-center"><div class="break-all font-mono text-lg font-black text-blue-600" dir="ltr" x-text="bitBin"></div><div class="text-xs text-gray-500">النتيجة (ثنائي)</div></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function labNumbers() {
            return {
                input: '42', base: 10, out: { dec: '', bin: '', hex: '', oct: '' }, convErr: '',
                validFor(s, b) {
                    const re = { 2: /^[01]+$/, 8: /^[0-7]+$/, 10: /^\d+$/, 16: /^[0-9a-fA-F]+$/ }[b];
                    return re ? re.test(s) : false;
                },
                convert() {
                    const s = (this.input || '').trim();
                    this.convErr = '';
                    if (!s) { this.out = { dec: '', bin: '', hex: '', oct: '' }; return; }
                    if (!this.validFor(s, this.base)) { this.convErr = 'قيمة غير صالحة لهذا النظام.'; this.out = { dec: '', bin: '', hex: '', oct: '' }; return; }
                    const n = parseInt(s, this.base);
                    this.out = { dec: n.toString(10), bin: n.toString(2), hex: n.toString(16).toUpperCase(), oct: n.toString(8) };
                },
                a: 12, b: 10, op: 'AND', bitOut: 0, bitBin: '0',
                calcBit() {
                    const x = this.a | 0, y = this.b | 0;
                    let r = 0;
                    switch (this.op) {
                        case 'AND': r = x & y; break;
                        case 'OR': r = x | y; break;
                        case 'XOR': r = x ^ y; break;
                        case 'NOT': r = ~x; break;
                        case 'SHL': r = x << 1; break;
                        case 'SHR': r = x >> 1; break;
                    }
                    this.bitOut = r;
                    this.bitBin = (r >>> 0).toString(2);
                },
            };
        }
    </script>
@endpush
