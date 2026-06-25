{{-- Live neural network: a perceptron learns a decision boundary --}}
<div x-data="labNeural()" x-init="$nextTick(() => draw())" class="card-luxury p-6">
    <h3 class="font-cairo text-lg font-black">شبكة عصبية حيّة — Perceptron</h3>
    <p class="mt-1 text-sm text-gray-500">انقر على اللوحة لإضافة نقاط، ثمّ درّب الخليّة لتجد الخطّ الفاصل بين الفئتين.</p>

    <div class="mt-4 flex flex-wrap items-center gap-3" dir="rtl">
        <span class="text-xs font-bold text-gray-500">الفئة المضافة:</span>
        <button @click="cls = 1" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-bold transition"
                :class="cls === 1 ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600'">
            <span class="inline-block h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span> الحمراء
        </button>
        <button @click="cls = -1" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-bold transition"
                :class="cls === -1 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600'">
            <span class="inline-block h-3 w-3 rounded-full bg-blue-500 ring-2 ring-white"></span> الزرقاء
        </button>
        <button @click="train()" :disabled="training || pts.length < 2" class="btn-primary text-sm disabled:opacity-50"><i class="fa-solid fa-bolt"></i> درّب</button>
        <button @click="seed()" :disabled="training" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-40">مثال جاهز</button>
        <button @click="clearPts()" :disabled="training" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 disabled:opacity-40">مسح</button>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-100 bg-white">
        <canvas x-ref="cv" @click="add($event)" class="block w-full cursor-crosshair" style="height:360px"></canvas>
    </div>

    <div class="mt-3 grid grid-cols-3 gap-3 text-center text-sm">
        <div class="rounded-xl bg-gray-50 p-3"><div class="text-xl font-black text-luxury-black" x-text="pts.length"></div><div class="text-xs text-gray-500">النقاط</div></div>
        <div class="rounded-xl bg-saudi-green/5 p-3"><div class="text-xl font-black text-saudi-green" dir="ltr"><span x-text="acc"></span>%</div><div class="text-xs text-gray-500">الدقّة</div></div>
        <div class="rounded-xl bg-royal-gold/10 p-3"><div class="text-xl font-black text-bronze" dir="ltr" x-text="epoch"></div><div class="text-xs text-gray-500">الحقبة (Epoch)</div></div>
    </div>
</div>

@push('scripts')
    <script>
        function labNeural() {
            return {
                pts: [], cls: 1, w: [0, 0, 0], lr: 0.06, training: false, acc: 0, epoch: 0,
                toXY(e) {
                    const c = this.$refs.cv, r = c.getBoundingClientRect();
                    const x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
                    return { x: x * 2 - 1, y: -(y * 2 - 1) };
                },
                add(e) {
                    if (this.training) return;
                    const p = this.toXY(e);
                    this.pts.push({ x: p.x, y: p.y, label: this.cls });
                    this.draw();
                },
                clearPts() { if (this.training) return; this.pts = []; this.w = [0, 0, 0]; this.acc = 0; this.epoch = 0; this.draw(); },
                seed() {
                    if (this.training) return;
                    this.pts = []; this.w = [0, 0, 0]; this.acc = 0; this.epoch = 0;
                    for (let i = 0; i < 14; i++) this.pts.push({ x: -0.2 - Math.random() * 0.7, y: 0.1 + Math.random() * 0.8 - (Math.random() * 0.3), label: 1 });
                    for (let i = 0; i < 14; i++) this.pts.push({ x: 0.2 + Math.random() * 0.7, y: -0.1 - Math.random() * 0.8 + (Math.random() * 0.3), label: -1 });
                    this.draw();
                },
                predict(p) { return (this.w[0] + this.w[1] * p.x + this.w[2] * p.y) >= 0 ? 1 : -1; },
                async train() {
                    if (this.training || this.pts.length < 2) return;
                    this.training = true;
                    this.w = [(Math.random() - 0.5) * 0.2, (Math.random() - 0.5) * 0.2, (Math.random() - 0.5) * 0.2];
                    for (let ep = 0; ep < 80; ep++) {
                        this.epoch = ep + 1;
                        let errs = 0;
                        for (const p of this.pts) {
                            if (this.predict(p) !== p.label) {
                                errs++;
                                this.w[0] += this.lr * p.label;
                                this.w[1] += this.lr * p.label * p.x;
                                this.w[2] += this.lr * p.label * p.y;
                            }
                        }
                        this.acc = Math.round((1 - errs / this.pts.length) * 100);
                        this.draw();
                        await new Promise(r => setTimeout(r, 70));
                        if (errs === 0) break;
                    }
                    this.training = false;
                },
                draw() {
                    const c = this.$refs.cv; if (!c) return;
                    const w = c.clientWidth || 500, h = 360; c.width = w; c.height = h;
                    const ctx = c.getContext('2d'); ctx.clearRect(0, 0, w, h);
                    const X = x => (x + 1) / 2 * w, Y = y => (1 - (y + 1) / 2) * h;
                    // grid
                    ctx.strokeStyle = '#f1f5f9'; ctx.lineWidth = 1;
                    for (let i = -1; i <= 1; i += 0.25) { ctx.beginPath(); ctx.moveTo(X(i), 0); ctx.lineTo(X(i), h); ctx.stroke(); ctx.beginPath(); ctx.moveTo(0, Y(i)); ctx.lineTo(w, Y(i)); ctx.stroke(); }
                    // decision boundary
                    if (this.w[1] !== 0 || this.w[2] !== 0) {
                        ctx.strokeStyle = '#006C35'; ctx.lineWidth = 3; ctx.beginPath();
                        if (this.w[2] !== 0) {
                            const ly = x => -(this.w[0] + this.w[1] * x) / this.w[2];
                            ctx.moveTo(X(-1), Y(ly(-1))); ctx.lineTo(X(1), Y(ly(1)));
                        } else {
                            const xv = -this.w[0] / this.w[1];
                            ctx.moveTo(X(xv), 0); ctx.lineTo(X(xv), h);
                        }
                        ctx.stroke();
                    }
                    // points
                    for (const p of this.pts) {
                        ctx.beginPath(); ctx.arc(X(p.x), Y(p.y), 6, 0, Math.PI * 2);
                        ctx.fillStyle = p.label === 1 ? '#ef4444' : '#3b82f6'; ctx.fill();
                        ctx.strokeStyle = '#fff'; ctx.lineWidth = 2; ctx.stroke();
                    }
                },
            };
        }
    </script>
@endpush
