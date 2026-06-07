@php
    // Build a de-duplicated field list: share-page link, a direct hotlink for
    // public assets, then the remaining share-kit codes.
    $fields = [['key' => 'page', 'label' => __('asset_admin.share_link'), 'code' => $asset->pageUrl()]];
    if ($asset->disk === 'public') {
        $fields[] = ['key' => 'direct', 'label' => __('asset_admin.direct_link'), 'code' => $asset->directUrl()];
    }
    $seen = array_map(fn ($f) => $f['code'], $fields);
    foreach ($kit as $b) {
        if (! in_array($b['code'], $seen, true)) {
            $fields[] = $b;
            $seen[] = $b['code'];
        }
    }

    $iconFor = fn ($k) => [
        'page' => 'fa-solid fa-link', 'direct' => 'fa-solid fa-link', 'html' => 'fa-solid fa-code',
        'markdown' => 'fa-brands fa-markdown', 'bbcode' => 'fa-solid fa-puzzle-piece',
        'thumb' => 'fa-solid fa-image', 'forum' => 'fa-solid fa-comments',
    ][$k] ?? 'fa-solid fa-code';

    $fmt = function ($b) {
        $b = (int) $b; if ($b <= 0) return '';
        $u = ['B','KB','MB','GB','TB']; $i = (int) floor(log($b, 1024));
        return round($b / (1024 ** $i), 1).' '.$u[min($i, 4)];
    };
    $ext = strtolower(pathinfo($asset->original_name, PATHINFO_EXTENSION));
@endphp

<div x-data="{ done: null, copy(t, k) { window.fnoonCopy(t).then(() => { this.done = k; setTimeout(() => { if (this.done === k) this.done = null; }, 1500); }); } }"
     class="sk-modal">

    {{-- preview / head --}}
    @if ($asset->isImage())
        <a href="{{ $asset->directUrl() }}" target="_blank" class="sk-preview">
            <img src="{{ $asset->thumbUrl() ?: $asset->directUrl() }}" alt="">
        </a>
    @else
        <div class="sk-card-head">
            <span class="sk-thumb sk-thumb--file">
                <i class="fa-solid {{ $asset->isPdf() ? 'fa-file-pdf' : 'fa-file-arrow-down' }}"></i>
            </span>
            <div class="min-w-0 flex-1">
                <div class="sk-name">{{ $asset->original_name }}</div>
                <div class="sk-meta">
                    <span class="sk-chip">{{ strtoupper($ext ?: $asset->kind) }}</span>
                    @if ($asset->size_bytes)<span dir="ltr">{{ $fmt($asset->size_bytes) }}</span>@endif
                    @if ($asset->pages)<span dir="ltr">{{ $asset->pages }} p</span>@endif
                </div>
            </div>
        </div>
    @endif

    {{-- fields --}}
    <div class="sk-codes">
        @foreach ($fields as $i => $f)
            <div class="sk-field" :class="done === 'f{{ $i }}' ? 'is-copied' : ''">
                <div class="sk-field-top">
                    <span class="sk-field-label">
                        <i class="{{ $iconFor($f['key'] ?? '') }}"></i>
                        <span>{{ $f['label'] }}</span>
                    </span>
                    <button type="button" @click="copy($refs['f{{ $i }}'].value, 'f{{ $i }}')" class="sk-copy">
                        <i class="fa-solid" :class="done === 'f{{ $i }}' ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="done === 'f{{ $i }}' ? '{{ __('asset_admin.copied') }}' : '{{ __('asset_admin.copy') }}'"></span>
                    </button>
                </div>
                <textarea x-ref="f{{ $i }}" readonly dir="ltr" onclick="this.select()"
                          rows="{{ \Illuminate\Support\Str::contains($f['code'], '<') ? 2 : 1 }}"
                          class="sk-value">{{ $f['code'] }}</textarea>
            </div>
        @endforeach
    </div>

    {{-- footer --}}
    <a href="{{ $asset->pageUrl() }}" target="_blank" class="sk-foot">
        <i class="fa-solid fa-up-right-from-square"></i>
        <span>{{ __('asset_admin.open_with_qr') }}</span>
    </a>
</div>
