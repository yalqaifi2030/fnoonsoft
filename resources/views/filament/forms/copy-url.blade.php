{{-- Read-only URL with a one-click copy button (used in Filament form placeholders). --}}
<div x-data="{ copied: false }" style="display:flex;align-items:center;gap:.5rem">
    <input type="text" readonly value="{{ $url }}" dir="ltr" x-ref="u" @focus="$event.target.select()"
           style="flex:1;min-width:0;border:1px solid rgba(0,0,0,.12);border-radius:.5rem;padding:.5rem .75rem;font-size:.85rem;background:#fff;color:#111827">
    <button type="button"
            @click="navigator.clipboard.writeText($refs.u.value).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
            style="white-space:nowrap;border-radius:.5rem;padding:.5rem .95rem;font-size:.85rem;font-weight:700;color:#fff;background:#006C35;border:0;cursor:pointer">
        <span x-text="copied ? @js(__('software.code.copied')) : @js(__('software.code.copy'))"></span>
    </button>
</div>
