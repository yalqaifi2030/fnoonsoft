{{-- Sorting algorithms visualizer --}}
<div x-data="labSorting()" x-init="shuffle()" class="card-luxury p-6">
    <h3 class="font-cairo text-lg font-black">محاكاة خوارزميات الترتيب</h3>

    <div class="mt-4 flex flex-wrap items-end gap-3" dir="ltr">
        <label><span class="text-xs font-bold text-gray-500">الخوارزمية</span>
            <select x-model="algo" :disabled="running" class="mt-1 rounded-xl border-gray-200">
                <option value="bubble">الترتيب الفقاعي (Bubble)</option>
                <option value="selection">ترتيب الاختيار (Selection)</option>
                <option value="insertion">ترتيب الإدراج (Insertion)</option>
            </select></label>
        <label><span class="text-xs font-bold text-gray-500">العناصر: <span x-text="n"></span></span>
            <input type="range" min="10" max="60" x-model.number="n" :disabled="running" @change="shuffle()" class="mt-2 block w-36"></label>
        <label><span class="text-xs font-bold text-gray-500">السرعة</span>
            <input type="range" min="1" max="100" x-model.number="speed" class="mt-2 block w-36"></label>
        <button @click="shuffle()" :disabled="running" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-40"><i class="fa-solid fa-shuffle"></i> خلط</button>
        <button @click="sort()" :disabled="running" class="btn-primary text-sm disabled:opacity-50"><i class="fa-solid fa-play"></i> ابدأ الترتيب</button>
    </div>

    <div class="mt-5 flex h-72 items-end gap-px rounded-2xl border border-gray-100 bg-gray-50 p-3" dir="ltr">
        <template x-for="(bar, idx) in arr" :key="idx">
            <div class="flex-1 rounded-t transition-[height] duration-75"
                 :class="bar.s === 'cmp' ? 'bg-amber-500' : (bar.s === 'done' ? 'bg-saudi-green' : 'bg-saudi-green/40')"
                 :style="'height:' + bar.v + '%'"></div>
        </template>
    </div>

    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#f59e0b"></span> قيد المقارنة</span>
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#006C35"></span> مُرتّب</span>
        <span class="ms-auto">المقارنات: <strong x-text="comparisons"></strong></span>
    </div>
</div>

@push('scripts')
    <script>
        function labSorting() {
            return {
                arr: [], algo: 'bubble', speed: 60, n: 30, running: false, comparisons: 0,
                shuffle() {
                    if (this.running) return;
                    this.comparisons = 0;
                    this.arr = Array.from({ length: this.n }, () => ({ v: Math.floor(Math.random() * 92) + 6, s: '' }));
                },
                sleep() { return new Promise(r => setTimeout(r, 102 - this.speed)); },
                async sort() {
                    if (this.running) return;
                    this.running = true;
                    this.comparisons = 0;
                    const a = this.arr;
                    try {
                        if (this.algo === 'bubble') {
                            for (let i = 0; i < a.length; i++) {
                                for (let j = 0; j < a.length - i - 1; j++) {
                                    a[j].s = 'cmp'; a[j + 1].s = 'cmp'; this.comparisons++;
                                    await this.sleep();
                                    if (a[j].v > a[j + 1].v) { const t = a[j].v; a[j].v = a[j + 1].v; a[j + 1].v = t; }
                                    a[j].s = ''; a[j + 1].s = '';
                                }
                                a[a.length - i - 1].s = 'done';
                            }
                        } else if (this.algo === 'selection') {
                            for (let i = 0; i < a.length; i++) {
                                let m = i;
                                for (let j = i + 1; j < a.length; j++) {
                                    a[j].s = 'cmp'; this.comparisons++;
                                    await this.sleep();
                                    if (a[j].v < a[m].v) m = j;
                                    a[j].s = '';
                                }
                                const t = a[i].v; a[i].v = a[m].v; a[m].v = t; a[i].s = 'done';
                            }
                        } else {
                            a[0].s = 'done';
                            for (let i = 1; i < a.length; i++) {
                                const key = a[i].v; let j = i - 1; a[i].s = 'cmp';
                                while (j >= 0 && a[j].v > key) {
                                    this.comparisons++; a[j].s = 'cmp';
                                    a[j + 1].v = a[j].v;
                                    await this.sleep();
                                    a[j].s = 'done'; j--;
                                }
                                a[j + 1].v = key; a[i].s = 'done';
                            }
                        }
                        for (const x of a) x.s = 'done';
                    } finally {
                        this.running = false;
                    }
                },
            };
        }
    </script>
@endpush
