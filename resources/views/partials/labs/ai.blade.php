{{-- AI playground — presets managed from admin (lab items) --}}
@php
    $presets = ($lab->activeItems ?? collect())->map(fn ($it) => [
        'id' => (string) $it->id,
        'name' => $it->title,
        'degree' => (int) $it->d('degree', 1),
        'points' => $it->d('points', ''),
    ])->values();
@endphp
<div x-data="regression(@js($presets))" x-init="init()" class="card-luxury p-4">
    <div class="flex items-center gap-3 mb-3 flex-wrap" dir="ltr">
        <select x-show="presets.length" @change="loadPreset($event.target.value)" class="rounded-lg border-gray-200 text-sm py-1.5">
            <option value="">{{ __('learn.ai.sample') }}…</option>
            <template x-for="p in presets" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
        </select>
        <button @click="clearPts()" class="text-sm px-3 py-1.5 rounded-lg text-gray-500 hover:bg-gray-100"><i class="fa-solid fa-eraser"></i> {{ __('learn.ai.clear') }}</button>
        <label class="text-sm text-gray-600 ms-2">Degree: <b x-text="degree"></b></label>
        <input type="range" min="1" max="5" x-model.number="degree" @input="draw()" class="accent-fuchsia-600 w-32">
        <div class="ms-auto flex items-center gap-3 text-sm">
            <span class="text-gray-500">{{ __('learn.ai.points') }}: <b x-text="points.length"></b></span>
            <span class="px-2 py-1 rounded-lg bg-fuchsia-50 text-fuchsia-700 font-mono" x-show="metrics">R² = <span x-text="metrics.r2"></span></span>
            <span class="px-2 py-1 rounded-lg bg-gray-100 text-gray-600 font-mono" x-show="metrics">MSE = <span x-text="metrics.mse"></span></span>
        </div>
    </div>
    <canvas x-ref="cv" width="900" height="340" @click="add($event)"
            class="w-full rounded-xl border border-royal-gold/15 cursor-crosshair" style="background:#fbfdfb"></canvas>
    <p class="text-xs text-gray-400 mt-2 text-center">{{ __('learn.ai.subtitle') }}</p>
</div>

@push('scripts')
<script>
    function regression(presets) {
        return {
            presets, points: [], degree: 1, metrics: null, ctx: null, canvas: null,
            init() { this.canvas = this.$refs.cv; this.ctx = this.canvas.getContext('2d'); this.draw(); },
            loadPreset(id) {
                const p = this.presets.find(x => x.id === id);
                if (!p) return;
                this.degree = p.degree || 1;
                this.points = (p.points || '').split(';').map(s => s.trim()).filter(Boolean).map(pair => {
                    const [x, y] = pair.split(',').map(Number); return { x, y };
                }).filter(pt => !isNaN(pt.x) && !isNaN(pt.y));
                this.draw();
            },
            add(e) {
                const r = this.canvas.getBoundingClientRect();
                const sx = this.canvas.width / r.width, sy = this.canvas.height / r.height;
                this.points.push({ x: (e.clientX - r.left) * sx, y: (e.clientY - r.top) * sy });
                this.draw();
            },
            clearPts() { this.points = []; this.metrics = null; this.draw(); },
            solve(A, b) {
                const n = b.length;
                for (let i = 0; i < n; i++) {
                    let p = i; for (let r = i + 1; r < n; r++) if (Math.abs(A[r][i]) > Math.abs(A[p][i])) p = r;
                    [A[i], A[p]] = [A[p], A[i]]; [b[i], b[p]] = [b[p], b[i]];
                    if (Math.abs(A[i][i]) < 1e-9) return null;
                    for (let r = i + 1; r < n; r++) { const f = A[r][i] / A[i][i]; for (let c = i; c < n; c++) A[r][c] -= f * A[i][c]; b[r] -= f * b[i]; }
                }
                const c = new Array(n).fill(0);
                for (let i = n - 1; i >= 0; i--) { let s = b[i]; for (let j = i + 1; j < n; j++) s -= A[i][j] * c[j]; c[i] = s / A[i][i]; }
                return c;
            },
            fit() {
                const d = this.degree, n = this.points.length;
                if (n < d + 1) return null;
                const A = [], b = [];
                for (let i = 0; i <= d; i++) {
                    A.push(new Array(d + 1).fill(0)); b.push(0);
                    for (let j = 0; j <= d; j++) for (const p of this.points) A[i][j] += Math.pow(p.x, i + j);
                    for (const p of this.points) b[i] += p.y * Math.pow(p.x, i);
                }
                return this.solve(A, b);
            },
            predict(c, x) { let y = 0; for (let k = 0; k < c.length; k++) y += c[k] * Math.pow(x, k); return y; },
            draw() {
                const ctx = this.ctx, W = this.canvas.width, H = this.canvas.height;
                ctx.clearRect(0, 0, W, H);
                ctx.strokeStyle = 'rgba(201,169,97,.15)'; ctx.lineWidth = 1;
                for (let x = 0; x < W; x += 45) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke(); }
                for (let y = 0; y < H; y += 45) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }
                const c = this.fit();
                if (c) {
                    ctx.strokeStyle = '#a855f7'; ctx.lineWidth = 3; ctx.beginPath();
                    for (let px = 0; px <= W; px += 4) { const py = this.predict(c, px); px === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py); }
                    ctx.stroke();
                    const ys = this.points.map(p => p.y), mean = ys.reduce((a, v) => a + v, 0) / ys.length;
                    let ssRes = 0, ssTot = 0;
                    for (const p of this.points) { const yh = this.predict(c, p.x); ssRes += (p.y - yh) ** 2; ssTot += (p.y - mean) ** 2; }
                    this.metrics = { r2: ssTot ? (1 - ssRes / ssTot).toFixed(3) : '1.000', mse: (ssRes / this.points.length).toFixed(0) };
                } else { this.metrics = null; }
                for (const p of this.points) {
                    ctx.beginPath(); ctx.arc(p.x, p.y, 5, 0, Math.PI * 2);
                    ctx.fillStyle = '#C9A961'; ctx.fill(); ctx.strokeStyle = '#8B6F47'; ctx.lineWidth = 1.5; ctx.stroke();
                }
            },
        };
    }
</script>
@endpush
