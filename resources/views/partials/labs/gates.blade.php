{{-- Logic gates simulator: live inputs, output and truth table --}}
<div x-data="labGates()" class="space-y-5">
    {{-- Gate picker --}}
    <div class="card-luxury p-5">
        <h3 class="mb-3 font-cairo text-base font-black">اختر البوّابة المنطقية</h3>
        <div class="flex flex-wrap gap-2" dir="ltr">
            <template x-for="g in gates" :key="g.k">
                <button @click="gate = g.k"
                        class="rounded-lg px-4 py-2 text-sm font-black transition"
                        :class="gate === g.k ? 'bg-saudi-green text-white shadow' : 'bg-gray-100 text-gray-600 hover:bg-saudi-green/10'"
                        x-text="g.label"></button>
            </template>
        </div>
    </div>

    {{-- Live circuit --}}
    <div class="card-luxury p-6">
        <div class="flex flex-col items-center justify-center gap-6 sm:flex-row sm:gap-10" dir="ltr">
            {{-- inputs --}}
            <div class="flex flex-col gap-4">
                <button @click="a = !a"
                        class="flex h-14 w-24 items-center justify-center rounded-xl text-2xl font-black text-white shadow transition"
                        :class="a ? 'bg-saudi-green' : 'bg-gray-300'">
                    <span x-text="'A = ' + (a ? 1 : 0)"></span>
                </button>
                <button x-show="!single" @click="b = !b"
                        class="flex h-14 w-24 items-center justify-center rounded-xl text-2xl font-black text-white shadow transition"
                        :class="b ? 'bg-saudi-green' : 'bg-gray-300'">
                    <span x-text="'B = ' + (b ? 1 : 0)"></span>
                </button>
            </div>

            {{-- gate body --}}
            <div class="flex flex-col items-center">
                <div class="flex h-20 w-32 items-center justify-center rounded-2xl border-2 border-saudi-green/30 bg-saudi-green/5 font-cairo text-xl font-black text-saudi-green"
                     x-text="gate"></div>
                <span class="mt-1 text-xs text-gray-400">البوّابة</span>
            </div>

            {{-- output bulb --}}
            <div class="flex flex-col items-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-full text-3xl font-black text-white shadow-lg transition"
                     :class="out ? 'bg-royal-gold shadow-royal-gold/40' : 'bg-gray-300'"
                     x-text="out ? 1 : 0"></div>
                <span class="mt-1 text-xs font-bold" :class="out ? 'text-royal-gold' : 'text-gray-400'">المخرج (Q)</span>
            </div>
        </div>
    </div>

    {{-- Truth table --}}
    <div class="card-luxury p-6">
        <h3 class="mb-3 font-cairo text-base font-black">جدول الحقيقة</h3>
        <div class="overflow-hidden rounded-xl border border-gray-100" dir="ltr">
            <table class="w-full text-center text-sm">
                <thead class="bg-gray-50 font-bold text-gray-500">
                    <tr>
                        <th class="py-2">A</th>
                        <th class="py-2" x-show="!single">B</th>
                        <th class="py-2 text-saudi-green">Q</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in table" :key="idx">
                        <tr class="border-t border-gray-100 transition"
                            :class="row.cur ? 'bg-royal-gold/10 font-black' : ''">
                            <td class="py-2" x-text="row.a"></td>
                            <td class="py-2" x-show="!single" x-text="row.b"></td>
                            <td class="py-2 font-bold" :class="row.o ? 'text-saudi-green' : 'text-gray-400'" x-text="row.o"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-gray-400">الصفّ المميّز يطابق المداخل الحالية.</p>
    </div>
</div>

@push('scripts')
    <script>
        function labGates() {
            return {
                gate: 'AND', a: false, b: false,
                gates: [
                    { k: 'AND', label: 'AND' }, { k: 'OR', label: 'OR' }, { k: 'NOT', label: 'NOT' },
                    { k: 'NAND', label: 'NAND' }, { k: 'NOR', label: 'NOR' }, { k: 'XOR', label: 'XOR' }, { k: 'XNOR', label: 'XNOR' },
                ],
                get single() { return this.gate === 'NOT'; },
                op(a, b) {
                    switch (this.gate) {
                        case 'AND': return a && b;
                        case 'OR': return a || b;
                        case 'NOT': return !a;
                        case 'NAND': return !(a && b);
                        case 'NOR': return !(a || b);
                        case 'XOR': return a !== b;
                        case 'XNOR': return a === b;
                    }
                    return false;
                },
                get out() { return this.op(this.a, this.b) ? 1 : 0; },
                get table() {
                    const rows = [];
                    if (this.single) {
                        [0, 1].forEach(a => rows.push({ a, b: 0, o: this.op(!!a, false) ? 1 : 0, cur: !!a === this.a }));
                    } else {
                        [0, 1].forEach(a => [0, 1].forEach(b =>
                            rows.push({ a, b, o: this.op(!!a, !!b) ? 1 : 0, cur: (!!a === this.a && !!b === this.b) })));
                    }
                    return rows;
                },
            };
        }
    </script>
@endpush
