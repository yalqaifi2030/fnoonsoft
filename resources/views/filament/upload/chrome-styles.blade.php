<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    /* ===================== HIDDEN SCROLLBARS ===================== */
    /* Loads only on the upload panel — hides scrollbars while keeping scroll. */
    .fi-body ::-webkit-scrollbar,
    .fi-body::-webkit-scrollbar,
    .fi-main::-webkit-scrollbar,
    .fi-sidebar-nav::-webkit-scrollbar,
    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }
    .fi-body,
    .fi-main,
    .fi-sidebar-nav,
    html,
    body {
        scrollbar-width: none !important;        /* Firefox */
        -ms-overflow-style: none !important;     /* IE/Edge */
    }

    /* ===================== UPLOAD PANEL CHROME ===================== */

    /* ---- Sidebar shell ---- */
    .fi-sidebar {
        border-inline-end: 1px solid rgba(201, 169, 97, .22);
        background: linear-gradient(180deg, #ffffff, #fbfaf6);
    }
    .dark .fi-sidebar {
        background: #0b1220;
        border-color: rgba(201, 169, 97, .16);
    }

    .fi-sidebar-header {
        border-bottom: 1px solid rgba(201, 169, 97, .18);
        box-shadow: 0 6px 18px -14px rgba(0, 108, 53, .45);
    }

    /* ---- Group labels ---- */
    .fi-sidebar-group-label {
        color: #9c8244;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    /* ---- Nav items ---- */
    .fi-sidebar-item-button {
        border-radius: .7rem;
        margin-inline: .35rem;
        font-weight: 600;
        transition: background-color .2s ease, color .2s ease, box-shadow .2s ease;
    }
    .fi-sidebar-item-button:hover {
        background: rgba(0, 108, 53, .08);
        color: #006C35;
    }
    .dark .fi-sidebar-item-button:hover {
        background: rgba(0, 108, 53, .20);
        color: #ffffff;
    }

    /* ---- Active item ---- */
    .fi-sidebar-item.fi-active .fi-sidebar-item-button {
        position: relative;
        background: linear-gradient(120deg, #006C35, #00582b);
        box-shadow: 0 10px 22px -12px rgba(0, 108, 53, .65);
    }
    /* Force the label white (Filament sets its own gray colour on the label) */
    .fi-sidebar-item.fi-active .fi-sidebar-item-button,
    .fi-sidebar-item.fi-active .fi-sidebar-item-button .fi-sidebar-item-label {
        color: #ffffff !important;
    }
    .fi-sidebar-item.fi-active .fi-sidebar-item-button .fi-sidebar-item-icon,
    .fi-sidebar-item.fi-active .fi-sidebar-item-button svg {
        color: #C9A961 !important;
    }
    .fi-sidebar-item.fi-active .fi-sidebar-item-button::before {
        content: '';
        position: absolute;
        inset-inline-start: -.35rem;
        top: 22%;
        bottom: 22%;
        width: 3px;
        border-radius: 3px;
        background: #C9A961;
    }

    /* ---- Collapse button ---- */
    .fi-sidebar-collapse-button .fi-icon-btn,
    .fi-sidebar-open-button .fi-icon-btn {
        color: #006C35;
    }

    /* ===================== TOPBAR ===================== */
    .fi-topbar::before {
        content: '';
        display: block;
        height: 3px;
        background: linear-gradient(90deg, #006C35, #C9A961, #006C35);
    }
    /* NB: never use backdrop-filter/filter/transform here — it creates a new
       containing block that clips & flips the user-menu dropdown. */
    .fi-topbar > nav {
        border-bottom: 1px solid rgba(201, 169, 97, .22);
        background: #ffffff;
    }
    .dark .fi-topbar > nav {
        background: #0b1220;
        border-color: rgba(201, 169, 97, .16);
    }

    /* ---- Sidebar footer (back-to-site) ---- */
    .fc-side-footer {
        margin: .5rem;
        border-top: 1px solid rgba(201, 169, 97, .2);
        padding-top: .65rem;
    }
    .fc-side-footer a {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .6rem .75rem;
        border-radius: .7rem;
        font-size: .85rem;
        font-weight: 600;
        color: #4b5563;
        transition: background-color .2s ease, color .2s ease;
    }
    .fc-side-footer a:hover {
        background: rgba(201, 169, 97, .12);
        color: #006C35;
    }
    .dark .fc-side-footer a { color: #9ca3af; }
    .dark .fc-side-footer a:hover { color: #fff; background: rgba(201,169,97,.16); }
</style>
@include('filament.partials.share-kit-styles')
