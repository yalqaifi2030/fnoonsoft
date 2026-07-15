{{-- First-party interest engine (privacy-safe).
     • Records ONLY the visitor's on-site behaviour (categories / types / tags they
       browse), stored in this browser's localStorage — never any device or file data.
     • Gated on the visitor's analytics cookie consent (fnoon_consent).
     • Powers the "Recommended for you" homepage slot (#fnoon-recommended).
     Product pages expose their context via window.fnoonPageCtx = {cat, type, tags[]}. --}}
<script>
(function () {
    var KEY = 'fnoon_interests';

    function consentOk() {
        var m = document.cookie.match(/(?:^|;\s*)fnoon_consent=([^;]+)/);
        var c = m ? decodeURIComponent(m[1]) : '';
        return c === 'all' || c.indexOf('analytics') > -1;
    }

    function read() {
        try { var o = JSON.parse(localStorage.getItem(KEY) || '{}'); return (o && typeof o === 'object') ? o : {}; }
        catch (e) { return {}; }
    }
    function write(o) { try { localStorage.setItem(KEY, JSON.stringify(o)); } catch (e) {} }

    function bump(map, key, w) {
        if (key === undefined || key === null || key === '') return;
        key = String(key);
        map[key] = (map[key] || 0) + w;
    }
    function trim(map, max) {
        var keys = Object.keys(map);
        if (keys.length <= max) return map;
        keys.sort(function (a, b) { return map[b] - map[a]; });
        var out = {};
        keys.slice(0, max).forEach(function (k) { out[k] = map[k]; });
        return out;
    }
    function topKeys(map, n) {
        map = map || {};
        return Object.keys(map).sort(function (a, b) { return map[b] - map[a]; }).slice(0, n);
    }

    function record(ctx, weight) {
        if (!consentOk() || !ctx) return;
        var o = read();
        o.cats = o.cats || {}; o.types = o.types || {}; o.tags = o.tags || {};
        bump(o.cats, ctx.cat, 3 * weight);
        bump(o.types, ctx.type, 2 * weight);
        (ctx.tags || []).forEach(function (t) { bump(o.tags, t, weight); });
        o.cats = trim(o.cats, 20); o.types = trim(o.types, 8); o.tags = trim(o.tags, 30);
        o.u = Date.now();
        write(o);
    }

    window.fnoonInterest = {
        read: read,
        record: record,
        signals: function () {
            var o = read();
            return { cat: topKeys(o.cats, 8), type: topKeys(o.types, 6), tag: topKeys(o.tags, 12) };
        },
        clear: function () {
            try { localStorage.removeItem(KEY); } catch (e) {}
            var el = document.getElementById('fnoon-recommended');
            if (el) { el.hidden = true; el.innerHTML = ''; }
        }
    };

    // 1) Record the current page (a product view = weight 1).
    if (window.fnoonPageCtx) record(window.fnoonPageCtx, 1);

    // 2) A download click is stronger intent — boost the current page's context.
    document.addEventListener('click', function (e) {
        if (window.fnoonPageCtx && e.target.closest('a[href*="/download/"], a[href*="/go/"], [data-dl-all]')) {
            record(window.fnoonPageCtx, 3);
        }
    }, true);

    // 3) Fill the "Recommended for you" slot when present (homepage).
    var slot = document.getElementById('fnoon-recommended');
    if (slot) {
        var isAuth = document.body.getAttribute('data-auth') === '1';
        var sig = window.fnoonInterest.signals();
        var have = sig.cat.length || sig.type.length || sig.tag.length;

        if (isAuth || (consentOk() && have)) {
            var qs = new URLSearchParams();
            if (sig.cat.length) qs.set('cat', sig.cat.join(','));
            if (sig.type.length) qs.set('type', sig.type.join(','));
            if (sig.tag.length) qs.set('tag', sig.tag.join(','));
            try {
                var dls = JSON.parse(localStorage.getItem('fnoon_downloads') || '[]');
                if (Array.isArray(dls) && dls.length) {
                    qs.set('exclude', dls.slice(0, 40).map(function (x) { return x && x.slug; }).filter(Boolean).join(','));
                }
            } catch (e) {}

            fetch(slot.dataset.endpoint + '?' + qs.toString(), { headers: { 'Accept': 'text/html' } })
                .then(function (r) { return r.status === 200 ? r.text() : ''; })
                .then(function (html) { if (html && html.trim()) { slot.innerHTML = html; slot.hidden = false; } })
                .catch(function () {});
        }
    }
})();
</script>
