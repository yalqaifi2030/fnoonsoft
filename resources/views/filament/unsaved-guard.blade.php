{{-- Warns before leaving a panel page that has unsaved form edits. --}}
<script>
    (function () {
        if (window.__fnoonFormGuard) return;
        window.__fnoonFormGuard = true;

        var dirty = false;

        function markDirty(e) {
            var t = e.target;
            // Only count fields inside an actual <form> (create/edit forms), not
            // global search / table filters.
            if (t && t.closest && t.closest('form')) dirty = true;
        }

        document.addEventListener('input', markDirty, true);
        document.addEventListener('change', markDirty, true);

        // A real save (form submit) or SPA navigation clears the flag.
        document.addEventListener('submit', function () { dirty = false; }, true);
        document.addEventListener('livewire:navigating', function () { dirty = false; });

        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    })();
</script>
