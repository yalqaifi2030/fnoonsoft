{{-- Code library — snippets managed from admin (lab items) --}}
@php
    use Illuminate\Support\Str;
    $snippets = ($lab->activeItems ?? collect())->map(fn ($it) => [
        'title' => $it->title,
        'cat' => $it->d('category', 'js'),
        'lang' => $it->d('lang', 'javascript'),
        'code' => $it->d('code', ''),
    ])->values();
@endphp
<div x-data="{ cat:'all', q:'', copiedKey:null, copy(key, el){ window.fnoonCopy(el.innerText); this.copiedKey=key; setTimeout(()=>this.copiedKey=null,1500); } }">
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <div class="flex items-center gap-1 overflow-x-auto" dir="ltr">
            @foreach (['all' => 'All', 'arduino' => 'Arduino', 'ai' => 'AI', 'js' => 'JavaScript', 'security' => 'Security'] as $k => $label)
                <button @click="cat='{{ $k }}'" class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap transition"
                        :class="cat==='{{ $k }}' ? 'bg-saudi-green text-white' : 'text-gray-500 hover:bg-saudi-green/10'">{{ $label }}</button>
            @endforeach
        </div>
        <div class="relative ms-auto">
            <i class="fa-solid fa-magnifying-glass absolute top-1/2 -translate-y-1/2 ms-3 text-gray-400 text-sm"></i>
            <input x-model="q" placeholder="Search…" class="rounded-lg border-gray-200 text-sm ps-9 py-1.5 w-48">
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4" dir="ltr">
        @forelse ($snippets as $i => $s)
            <div class="card-luxury overflow-hidden"
                 x-show="(cat==='all' || cat==='{{ $s['cat'] }}') && @js(Str::lower($s['title'])).includes(q.toLowerCase())">
                <div class="flex items-center justify-between p-2 border-b border-royal-gold/10 bg-gray-50">
                    <span class="text-sm font-bold ps-2">{{ $s['title'] }}</span>
                    <button @click="copy('s{{ $i }}', $refs.code{{ $i }})" class="px-3 py-1 rounded-lg text-sm font-bold text-saudi-green hover:bg-saudi-green/10">
                        <i class="fa-solid" :class="copiedKey==='s{{ $i }}' ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copiedKey==='s{{ $i }}' ? '{{ __('learn.snippets.copied') }}' : '{{ __('learn.snippets.copy') }}'"></span>
                    </button>
                </div>
                <pre class="!m-0"><code x-ref="code{{ $i }}" class="language-{{ $s['lang'] }}">{{ $s['code'] }}</code></pre>
            </div>
        @empty
            <p class="text-gray-400 col-span-2 text-center py-8">—</p>
        @endforelse
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <style>.card-luxury pre{margin:0}.card-luxury pre code{display:block;padding:1rem;font-size:.8rem;line-height:1.6;background:#0d1117}</style>
@endpush
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>document.addEventListener('DOMContentLoaded', () => { if (window.hljs) hljs.highlightAll(); });</script>
@endpush
