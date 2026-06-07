<style>
    /* ===================== SHARE KIT (results panel + share modal) ===================== */
    .sk-list { display: flex; flex-direction: column; gap: 1rem; padding: 1.25rem; }
    .sk-modal { display: flex; flex-direction: column; gap: .9rem; }
    .sk-card { border: 1px solid #eef0f2; border-radius: 1rem; padding: 1rem 1.1rem; background: #fff; transition: box-shadow .2s; }
    .sk-card:hover { box-shadow: 0 8px 24px -16px rgba(0,108,53,.4); }
    .dark .sk-card { border-color: rgba(255,255,255,.08); background: #0b1220; }
    .sk-card-head { display: flex; align-items: center; gap: .85rem; }
    .sk-thumb { width: 3.25rem; height: 3.25rem; flex: 0 0 auto; border-radius: .8rem; overflow: hidden; border: 1px solid #eef0f2; display: flex; align-items: center; justify-content: center; }
    .sk-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .sk-thumb--file { background: linear-gradient(135deg, #e9f6ef, #f8fafc); color: #006C35; font-size: 1.3rem; }
    .dark .sk-thumb--file { background: rgba(0,108,53,.12); }
    .sk-preview { display: block; border-radius: .9rem; overflow: hidden; border: 1px solid #eef0f2; background: repeating-conic-gradient(#f3f4f6 0% 25%, #fff 0% 50%) 0 / 22px 22px; }
    .dark .sk-preview { border-color: rgba(255,255,255,.08); }
    .sk-preview img { display: block; margin: 0 auto; max-height: 13rem; width: auto; }
    .sk-name { font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dark .sk-name { color: #fff; }
    .sk-meta { display: flex; align-items: center; gap: .5rem; margin-top: .2rem; font-size: .72rem; color: #9ca3af; }
    .sk-chip { text-transform: uppercase; letter-spacing: .04em; font-weight: 700; color: #006C35; background: rgba(0,108,53,.08); padding: .08rem .45rem; border-radius: .4rem; }
    .sk-page-btn { margin-inline-start: auto; display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: .65rem; background: #006C35; color: #fff; font-size: .78rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: background .15s; }
    .sk-page-btn:hover { background: #00582b; color: #fff; }
    .sk-codes { display: grid; gap: .6rem; }
    @media (min-width: 768px) { .sk-codes { grid-template-columns: 1fr 1fr; } }
    .sk-field { border: 1px solid #eef0f2; border-radius: .75rem; padding: .55rem .7rem; background: #fbfcfd; transition: border-color .2s, background .2s; }
    .dark .sk-field { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
    .sk-field.is-copied { border-color: #006C35; background: rgba(0,108,53,.06); }
    .sk-field-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .4rem; gap: .5rem; }
    .sk-field-label { display: inline-flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 600; color: #6b7280; min-width: 0; }
    .sk-field-label span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sk-field-label i { color: #9ca3af; font-size: .72rem; flex: 0 0 auto; }
    .sk-copy { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700; color: #006C35; background: none; border: none; cursor: pointer; padding: .15rem .4rem; border-radius: .45rem; transition: background .15s; flex: 0 0 auto; }
    .sk-copy:hover { background: rgba(0,108,53,.1); }
    .sk-value { width: 100%; border: none; background: transparent; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .72rem; color: #4b5563; outline: none; resize: none; }
    .dark .sk-value { color: #cbd5e1; }
    .sk-foot { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 700; color: #006C35; text-decoration: none; }
</style>
