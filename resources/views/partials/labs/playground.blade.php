{{-- Live code playground — templates managed from admin (lab items) --}}
@php
    $tpls = ($lab->activeItems ?? collect())->map(fn ($it) => [
        'id' => (string) $it->id,
        'label' => $it->title,
        'html' => $it->d('html', ''),
        'css' => $it->d('css', ''),
        'js' => $it->d('js', ''),
    ])->values();
@endphp
<div x-data="codePlayground(@js($tpls))" x-init="init()" class="grid lg:grid-cols-2 gap-4">
    {{-- Editor --}}
    <div class="card-luxury overflow-hidden flex flex-col">
        <div class="flex items-center gap-1 p-2 border-b border-royal-gold/10 bg-gray-50 flex-wrap" dir="ltr">
            <template x-for="t in ['html','css','js']" :key="t">
                <button @click="tab=t" class="px-3 py-1.5 rounded-lg text-sm font-bold uppercase transition"
                        :class="tab===t ? 'bg-saudi-green text-white' : 'text-gray-500 hover:bg-saudi-green/10'" x-text="t"></button>
            </template>
            <select x-show="templates.length" x-model="current" @change="loadTemplate()" class="ms-2 rounded-lg border-gray-200 text-xs py-1.5">
                <template x-for="t in templates" :key="t.id">
                    <option :value="t.id" x-text="t.label"></option>
                </template>
            </select>
            <div class="ms-auto flex gap-1">
                <button @click="run()" class="px-3 py-1.5 rounded-lg text-sm font-bold bg-royal-gold text-luxury-black"><i class="fa-solid fa-play"></i> {{ __('learn.playground.run') }}</button>
                <button @click="download()" class="px-3 py-1.5 rounded-lg text-sm text-gray-500 hover:bg-gray-100" title="Download"><i class="fa-solid fa-download"></i></button>
                <button @click="reset()" class="px-3 py-1.5 rounded-lg text-sm text-gray-500 hover:bg-gray-100"><i class="fa-solid fa-rotate-left"></i></button>
            </div>
        </div>
        <template x-for="t in ['html','css','js']" :key="t">
            <textarea x-show="tab===t" x-model="code[t]" @input.debounce.500ms="run()" spellcheck="false" dir="ltr"
                      class="w-full h-[26rem] p-3 font-mono text-sm bg-luxury-black border-0 focus:ring-0 resize-none"
                      :class="{'text-green-300':t==='html','text-sky-300':t==='css','text-amber-200':t==='js'}"></textarea>
        </template>
    </div>

    {{-- Preview + console --}}
    <div class="flex flex-col gap-4">
        <div class="card-luxury overflow-hidden flex flex-col">
            <div class="p-2 border-b border-royal-gold/10 bg-gray-50 text-xs font-semibold text-gray-500"><i class="fa-solid fa-eye text-saudi-green"></i> {{ __('learn.playground.preview') }}</div>
            <iframe x-ref="frame" sandbox="allow-scripts allow-modals" class="w-full h-72 bg-white" title="preview"></iframe>
        </div>
        <div class="card-luxury overflow-hidden flex flex-col">
            <div class="p-2 border-b border-royal-gold/10 bg-luxury-black text-xs font-semibold text-gray-300 flex items-center justify-between" dir="ltr">
                <span><i class="fa-solid fa-terminal text-green-400"></i> Console</span>
                <button @click="logs=[]" class="text-gray-500 hover:text-white"><i class="fa-solid fa-ban"></i></button>
            </div>
            <div class="h-32 overflow-y-auto p-3 font-mono text-xs bg-luxury-black space-y-1" dir="ltr">
                <template x-for="(l,i) in logs" :key="i">
                    <div :class="{'text-red-400':l.type==='error','text-amber-300':l.type==='warn','text-gray-300':l.type==='log'||l.type==='info'}">
                        <span class="text-gray-600">›</span> <span x-text="l.msg"></span>
                    </div>
                </template>
                <div x-show="!logs.length" class="text-gray-600">// console output appears here</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function codePlayground(templates) {
        const blank = { html: '<h1>Hello, Fnoon! 👋</h1>', css: 'body{font-family:sans-serif;text-align:center;padding:24px}\nh1{color:#006C35}', js: "console.log('Ready');" };
        return {
            templates, tab: 'html', logs: [],
            current: templates.length ? templates[0].id : '',
            code: templates.length ? { html: templates[0].html, css: templates[0].css, js: templates[0].js } : { ...blank },
            loadTemplate() {
                const t = this.templates.find(x => x.id === this.current);
                if (t) { this.code = { html: t.html, css: t.css, js: t.js }; this.run(); }
            },
            run() {
                this.logs = [];
                const hook = '<scr' + 'ipt>(function(){const s=(t,a)=>parent.postMessage({__pg:1,type:t,msg:a.map(x=>{try{return typeof x==="object"?JSON.stringify(x):String(x)}catch(e){return String(x)}}).join(" ")},"*");["log","warn","error","info"].forEach(k=>{const o=console[k];console[k]=function(){s(k,[].slice.call(arguments));o.apply(console,arguments)}});window.onerror=function(m){s("error",[m]);return false};})();</scr' + 'ipt>';
                this.$refs.frame.srcdoc = '<style>' + this.code.css + '</st' + 'yle>' + hook + this.code.html + '<scr' + 'ipt>try{' + this.code.js + '}catch(e){console.error(e.message)}</scr' + 'ipt>';
            },
            download() {
                const doc = '<!doctype html><html><head><meta charset="utf-8"><style>' + this.code.css + '</style></head><body>' + this.code.html + '<scr' + 'ipt>' + this.code.js + '</scr' + 'ipt></body></html>';
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([doc], { type: 'text/html' }));
                a.download = 'fnoon-playground.html'; a.click();
            },
            reset() { this.loadTemplate(); },
            init() {
                window.addEventListener('message', e => { if (e.data && e.data.__pg) this.logs.push({ type: e.data.type, msg: e.data.msg }); });
                this.run();
            },
        };
    }
</script>
@endpush
