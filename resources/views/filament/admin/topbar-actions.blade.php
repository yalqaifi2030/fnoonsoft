<form method="POST" action="{{ route('system.clear-cache') }}"
      x-data
      @submit="if (! confirm(@js(__('admin.cache_confirm')))) $event.preventDefault()"
      class="fc-topbar-action">
    @csrf
    <button type="submit" title="{{ __('admin.clear_cache') }}" aria-label="{{ __('admin.clear_cache') }}"
            class="fc-cache-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="m13 11 9-9" />
            <path d="M14.6 12.6c.8.8.9 2.1.2 3L10 22l-8-8 6.4-4.8c.9-.7 2.2-.6 3 .2Z" />
            <path d="m6.8 10.4 6.8 6.8" />
            <path d="m5 17 1.4-1.4" />
        </svg>
    </button>
</form>

<style>
    .fc-topbar-action { display: inline-flex; align-items: center; }
    .fc-cache-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.25rem; height: 2.25rem; border-radius: .6rem;
        color: #6b7280; background: transparent; transition: color .15s, background .15s;
    }
    .fc-cache-btn svg { width: 1.25rem; height: 1.25rem; }
    .fc-cache-btn:hover { color: #006C35; background: rgba(0, 108, 53, .08); }
    .dark .fc-cache-btn { color: #9ca3af; }
    .dark .fc-cache-btn:hover { color: #34d399; background: rgba(0, 108, 53, .18); }
</style>
