{{-- Arduino simulator — sketches managed from admin (lab items) --}}
@php
    $sketches = ($lab->activeItems ?? collect())->map(fn ($it) => [
        'id' => (string) $it->id,
        'name' => $it->title,
        'type' => $it->d('type', 'blink'),
        'code' => $it->d('code', ''),
        'delay' => (int) $it->d('delay', 500),
    ])->values();
@endphp
<div x-data="arduinoSim(@js($sketches))" class="grid lg:grid-cols-2 gap-4">
    {{-- Virtual board --}}
    <div class="card-luxury p-8 flex flex-col items-center" style="background:linear-gradient(160deg,#0b3d2e,#0b1220)">
        <div class="flex gap-2 mb-6 flex-wrap justify-center" dir="ltr">
            <template x-for="s in sketches" :key="s.id">
                <button @click="select(s.id)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                        :class="current && current.id===s.id ? 'bg-royal-gold text-luxury-black' : 'bg-white/10 text-gray-300 hover:bg-white/20'" x-text="s.name"></button>
            </template>
        </div>

        {{-- Traffic light --}}
        <div x-show="type()==='traffic'" class="flex flex-col gap-3 items-center bg-black/30 rounded-2xl p-4">
            <div class="h-12 w-12 rounded-full transition-all" :style="tl==='red' ? 'background:#ff3b3b;box-shadow:0 0 30px 8px rgba(255,59,59,.8)' : 'background:#3a0d0d'"></div>
            <div class="h-12 w-12 rounded-full transition-all" :style="tl==='yellow' ? 'background:#ffd23b;box-shadow:0 0 30px 8px rgba(255,210,59,.8)' : 'background:#3a350d'"></div>
            <div class="h-12 w-12 rounded-full transition-all" :style="tl==='green' ? 'background:#3bff6b;box-shadow:0 0 30px 8px rgba(59,255,107,.8)' : 'background:#0d3a18'"></div>
        </div>

        {{-- Single LED --}}
        <div x-show="type()!=='traffic'" class="flex flex-col items-center">
            <div class="h-28 w-28 rounded-full transition-all duration-100" :style="ledStyle()"></div>
            <div class="mt-4 font-mono text-sm" :class="brightness>0 ? 'text-red-400':'text-gray-500'"
                 x-text="type()==='fade' ? ('PWM: '+brightness) : (brightness>0 ? '{{ __('learn.arduino.on') }}' : '{{ __('learn.arduino.off') }}')"></div>
        </div>

        <div class="mt-8 w-full max-w-xs" x-show="type()==='blink'">
            <label class="block text-xs text-gray-300 mb-2" dir="ltr">{{ __('learn.arduino.delay') }}: <span x-text="delay"></span></label>
            <input type="range" min="100" max="1500" step="100" x-model.number="delay" class="w-full accent-royal-gold">
        </div>

        <button @click="toggle()" class="btn-gold mt-6 w-full max-w-xs justify-center" x-text="running ? '{{ __('learn.arduino.stop') }}' : '{{ __('learn.arduino.start') }}'"></button>
    </div>

    {{-- Sketch code --}}
    <div class="card-luxury overflow-hidden">
        <div class="p-2 border-b border-royal-gold/10 bg-gray-50 text-xs font-semibold text-gray-500"><i class="fa-solid fa-file-code text-sky-600"></i> {{ __('learn.arduino.code') }}</div>
        <pre class="p-4 font-mono text-sm bg-luxury-black text-sky-200 overflow-auto leading-relaxed h-[26rem]" dir="ltr" x-text="current ? current.code : ''"></pre>
    </div>
</div>

@push('scripts')
<script>
    function arduinoSim(sketches) {
        return {
            sketches,
            current: sketches.length ? sketches[0] : null,
            running: false, timer: null, brightness: 0, tl: 'red',
            delay: sketches.length ? sketches[0].delay : 500,
            _dir: 1, _i: 0, _sos: [], _t: 0,
            type() { return this.current ? this.current.type : 'blink'; },
            select(id) { this.stop(); this.current = this.sketches.find(s => s.id === id); this.delay = this.current.delay || 500; },
            toggle() { this.running ? this.stop() : this.start(); },
            start() { if (!this.current) return; this.running = true; this._i = 0; this._t = 0; this['run_' + this.type()](); },
            stop() { this.running = false; this.brightness = 0; this.tl = 'red'; clearTimeout(this.timer); },
            ledStyle() {
                const b = this.brightness / 255;
                return b > 0 ? `background:rgba(255,59,59,${0.25 + b * 0.75});box-shadow:0 0 ${10 + b * 40}px ${b * 14}px rgba(255,59,59,${b})` : 'background:#3a0d0d;box-shadow:none';
            },
            run_blink() { this.brightness = this.brightness > 0 ? 0 : 255; this.timer = setTimeout(() => this.run_blink(), this.delay); },
            run_fade() {
                this.brightness += this._dir * 15;
                if (this.brightness >= 255) { this.brightness = 255; this._dir = -1; }
                if (this.brightness <= 0) { this.brightness = 0; this._dir = 1; }
                this.timer = setTimeout(() => this.run_fade(), 40);
            },
            run_traffic() {
                const seq = [['green', 3000], ['yellow', 800], ['red', 3000]];
                this.tl = seq[this._t][0];
                const d = seq[this._t][1];
                this._t = (this._t + 1) % seq.length;
                this.timer = setTimeout(() => this.run_traffic(), d);
            },
            run_sos() {
                if (this._i === 0) this._sos = this.morse('SOS');
                const step = this._sos[this._i];
                this.brightness = step.on ? 255 : 0;
                this._i = (this._i + 1) % this._sos.length;
                this.timer = setTimeout(() => this.run_sos(), step.t);
            },
            morse(text) {
                const map = { S: '...', O: '---' }, unit = 200, out = [];
                for (const ch of text) {
                    for (const sym of map[ch]) { out.push({ on: true, t: sym === '-' ? unit * 3 : unit }); out.push({ on: false, t: unit }); }
                    out.push({ on: false, t: unit * 2 });
                }
                out.push({ on: false, t: unit * 6 });
                return out;
            },
        };
    }
</script>
@endpush
