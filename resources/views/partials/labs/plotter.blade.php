{{-- Function plotter lab: graph y = f(x) on a canvas --}}
<div x-data="labPlotter()" x-init="$nextTick(() => plot())" class="card-luxury p-6">
    <h3 class="font-cairo text-lg font-black">راسم الدوال — y = f(x)</h3>
    <div class="mt-4 flex flex-wrap items-end gap-3" dir="ltr">
        <label class="flex-1 min-w-[12rem]"><span class="text-xs font-bold text-gray-500">f(x) =</span>
            <input type="text" x-model="expr" @keydown.enter="plot()" dir="ltr" placeholder="sin(x)"
                   class="mt-1 w-full rounded-xl border-gray-200 font-mono"></label>
        <label><span class="text-xs font-bold text-gray-500">من x</span>
            <input type="number" step="any" x-model.number="xmin" class="mt-1 w-24 rounded-xl border-gray-200"></label>
        <label><span class="text-xs font-bold text-gray-500">إلى x</span>
            <input type="number" step="any" x-model.number="xmax" class="mt-1 w-24 rounded-xl border-gray-200"></label>
        <button @click="plot()" class="btn-primary text-sm"><i class="fa-solid fa-chart-line"></i> ارسم</button>
    </div>
    <p x-show="err" x-text="err" class="mt-2 text-sm font-semibold text-red-600"></p>

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-100 bg-white">
        <canvas x-ref="cv" class="block w-full" style="height:340px"></canvas>
    </div>

    <p class="mt-3 text-xs text-gray-400">جرّب:
        <template x-for="ex in ['sin(x)','x^2','x^3 - 3*x','cos(x)*x','sqrt(abs(x))','exp(-x*x)']" :key="ex">
            <button @click="expr = ex; plot()" class="mx-1 font-mono font-bold text-saudi-green" x-text="ex"></button>
        </template>
    </p>
    <p class="mt-1 text-[11px] text-gray-400">الدوال المدعومة: sin, cos, tan, sqrt, abs, exp, log, pow، الثوابت PI و E، والعملية ^ للأُسّ.</p>
</div>

@push('scripts')
    <script>
        function labPlotter() {
            return {
                expr: 'sin(x)', xmin: -10, xmax: 10, err: '',
                safe(e) { return /^[-+*/(). ,0-9xXa-zA-Z^]*$/.test(e); },
                compile(e) {
                    e = e.replace(/\^/g, '**');
                    // Math scope: sin(x), cos(x), sqrt, PI, E, …
                    return new Function('x', 'with (Math) { return (' + e + '); }');
                },
                plot() {
                    this.err = '';
                    const c = this.$refs.cv;
                    if (!c) return;
                    const w = c.clientWidth || 600, h = 340;
                    c.width = w; c.height = h;
                    const ctx = c.getContext('2d');
                    ctx.clearRect(0, 0, w, h);

                    let f;
                    try { if (!this.safe(this.expr)) throw 0; f = this.compile(this.expr); const t = f(1); if (typeof t !== 'number') throw 0; }
                    catch (e) { this.err = 'دالّة غير صالحة. استخدم x والدوال المدعومة فقط.'; return; }

                    const xmin = parseFloat(this.xmin), xmax = parseFloat(this.xmax);
                    if (!(xmax > xmin)) { this.err = 'المدى غير صحيح (من < إلى).'; return; }

                    const ys = [];
                    for (let i = 0; i <= w; i++) { const x = xmin + (xmax - xmin) * i / w; let y; try { y = f(x); } catch (e) { y = NaN; } if (isFinite(y)) ys.push(y); }
                    let ymin = Math.min.apply(null, ys), ymax = Math.max.apply(null, ys);
                    if (!isFinite(ymin) || !isFinite(ymax) || ymin === ymax) { ymin = -1; ymax = 1; }
                    const pad = (ymax - ymin) * 0.12 || 1; ymin -= pad; ymax += pad;

                    const X = x => (x - xmin) / (xmax - xmin) * w;
                    const Y = y => h - (y - ymin) / (ymax - ymin) * h;

                    // grid + axes
                    ctx.strokeStyle = '#eef0f2'; ctx.lineWidth = 1;
                    for (let gx = Math.ceil(xmin); gx <= xmax; gx++) { ctx.beginPath(); ctx.moveTo(X(gx), 0); ctx.lineTo(X(gx), h); ctx.stroke(); }
                    ctx.strokeStyle = '#cbd5e1'; ctx.lineWidth = 1.5;
                    if (ymin < 0 && ymax > 0) { ctx.beginPath(); ctx.moveTo(0, Y(0)); ctx.lineTo(w, Y(0)); ctx.stroke(); }
                    if (xmin < 0 && xmax > 0) { ctx.beginPath(); ctx.moveTo(X(0), 0); ctx.lineTo(X(0), h); ctx.stroke(); }

                    // curve
                    ctx.strokeStyle = '#006C35'; ctx.lineWidth = 2.5; ctx.beginPath();
                    let started = false;
                    for (let i = 0; i <= w; i++) {
                        const x = xmin + (xmax - xmin) * i / w;
                        let y; try { y = f(x); } catch (e) { y = NaN; }
                        if (!isFinite(y) || Math.abs(y) > 1e6) { started = false; continue; }
                        const py = Y(y);
                        if (!started) { ctx.moveTo(i, py); started = true; } else ctx.lineTo(i, py);
                    }
                    ctx.stroke();
                },
            };
        }
    </script>
@endpush
