{{-- Pathfinding visualizer: draw walls, run BFS / A* on a grid --}}
<div x-data="labPath()" x-init="build()" @mouseup.window="drawing = false" class="card-luxury p-6">
    <h3 class="font-cairo text-lg font-black">مستكشف المسارات</h3>
    <p class="mt-1 text-sm text-gray-500">اسحب الفأرة على الشبكة لرسم الجدران، ثمّ شغّل الخوارزمية.</p>

    <div class="mt-4 flex flex-wrap items-end gap-3" dir="ltr">
        <label><span class="text-xs font-bold text-gray-500">الخوارزمية</span>
            <div class="mt-1 w-44" @fnoon-select-change="algo = $event.detail">
                <x-select name="path_algo" value="bfs"
                    :options="['bfs' => 'BFS (أقصر مسار)', 'astar' => 'A* (موجّهة بالاستدلال)']" />
            </div>
        </label>
        <label><span class="text-xs font-bold text-gray-500">السرعة</span>
            <input type="range" min="1" max="80" x-model.number="speed" class="mt-2 block w-36"></label>
        <button @click="run()" :disabled="running" class="btn-primary text-sm disabled:opacity-50"><i class="fa-solid fa-play"></i> ابدأ البحث</button>
        <button @click="clearPath()" :disabled="running" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-40">مسح المسار</button>
        <button @click="clearWalls()" :disabled="running" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-40">مسح الجدران</button>
        <span class="text-sm font-bold" :class="found ? 'text-saudi-green' : 'text-rose-600'" x-text="statusMsg"></span>
    </div>

    <div class="mt-5 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 p-2 select-none">
        <div class="grid gap-px" :style="'grid-template-columns: repeat(' + cols + ', minmax(0, 1fr))'" dir="ltr">
            <template x-for="(cell, i) in cells" :key="i">
                <div @mousedown.prevent="drawing = true; paint(i)"
                     @mouseenter="drawing && paint(i)"
                     class="aspect-square rounded-[2px] transition-colors duration-100"
                     :class="cellClass(cell, i)"></div>
            </template>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-500">
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#10b981"></span> البداية</span>
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#f43f5e"></span> الهدف</span>
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#374151"></span> جدار</span>
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:rgba(0,108,53,.3)"></span> مُستكشَف</span>
        <span><span class="inline-block h-3 w-3 rounded-sm align-middle" style="background:#fbbf24"></span> المسار</span>
    </div>
</div>

@push('scripts')
    <script>
        function labPath() {
            return {
                cols: 25, rows: 13, cells: [], start: 0, end: 0,
                running: false, drawing: false, algo: 'bfs', speed: 55, statusMsg: '', found: false,
                build() {
                    this.cells = [];
                    for (let r = 0; r < this.rows; r++)
                        for (let c = 0; c < this.cols; c++)
                            this.cells.push({ r, c, wall: false, state: '' });
                    this.start = this.idx((this.rows / 2) | 0, 2);
                    this.end = this.idx((this.rows / 2) | 0, this.cols - 3);
                },
                idx(r, c) { return r * this.cols + c; },
                cellClass(cell, i) {
                    if (i === this.start) return 'bg-emerald-500';
                    if (i === this.end) return 'bg-rose-500';
                    if (cell.wall) return 'bg-gray-700';
                    if (cell.state === 'path') return 'bg-amber-400';
                    if (cell.state === 'visited') return 'bg-saudi-green/30';
                    return 'bg-white';
                },
                paint(i) {
                    if (this.running || i === this.start || i === this.end) return;
                    this.cells[i].wall = !this.cells[i].wall;
                },
                clearWalls() { if (this.running) return; this.cells.forEach(c => { c.wall = false; c.state = ''; }); this.statusMsg = ''; },
                clearPath() { if (this.running) return; this.cells.forEach(c => c.state = ''); this.statusMsg = ''; },
                sleep() { return new Promise(r => setTimeout(r, 84 - this.speed)); },
                neighbors(i) {
                    const c = this.cells[i], res = [];
                    [[-1, 0], [1, 0], [0, -1], [0, 1]].forEach(([dr, dc]) => {
                        const nr = c.r + dr, nc = c.c + dc;
                        if (nr >= 0 && nr < this.rows && nc >= 0 && nc < this.cols) {
                            const ni = this.idx(nr, nc);
                            if (!this.cells[ni].wall) res.push(ni);
                        }
                    });
                    return res;
                },
                async run() {
                    if (this.running) return;
                    this.running = true; this.found = false; this.statusMsg = '';
                    this.cells.forEach(c => c.state = '');
                    const prev = new Array(this.cells.length).fill(-1);
                    const visited = new Array(this.cells.length).fill(false);
                    const ok = this.algo === 'astar' ? await this.astar(prev, visited) : await this.bfs(prev, visited);
                    if (ok) {
                        let cur = prev[this.end]; const path = [];
                        while (cur !== -1 && cur !== this.start) { path.push(cur); cur = prev[cur]; }
                        for (const p of path.reverse()) { this.cells[p].state = 'path'; await this.sleep(); }
                        this.found = true; this.statusMsg = 'تمّ إيجاد المسار ✓';
                    } else {
                        this.statusMsg = 'لا يوجد مسار ✗';
                    }
                    this.running = false;
                },
                async bfs(prev, visited) {
                    const q = [this.start]; visited[this.start] = true;
                    while (q.length) {
                        const cur = q.shift();
                        if (cur === this.end) return true;
                        if (cur !== this.start) { this.cells[cur].state = 'visited'; await this.sleep(); }
                        for (const n of this.neighbors(cur)) if (!visited[n]) { visited[n] = true; prev[n] = cur; q.push(n); }
                    }
                    return false;
                },
                async astar(prev, visited) {
                    const h = i => { const c = this.cells[i], e = this.cells[this.end]; return Math.abs(c.r - e.r) + Math.abs(c.c - e.c); };
                    const g = new Array(this.cells.length).fill(Infinity); g[this.start] = 0;
                    const open = [this.start];
                    while (open.length) {
                        open.sort((x, y) => (g[x] + h(x)) - (g[y] + h(y)));
                        const cur = open.shift();
                        if (cur === this.end) return true;
                        if (visited[cur]) continue;
                        visited[cur] = true;
                        if (cur !== this.start) { this.cells[cur].state = 'visited'; await this.sleep(); }
                        for (const n of this.neighbors(cur)) {
                            const ng = g[cur] + 1;
                            if (ng < g[n]) { g[n] = ng; prev[n] = cur; if (!visited[n]) open.push(n); }
                        }
                    }
                    return false;
                },
            };
        }
    </script>
@endpush
