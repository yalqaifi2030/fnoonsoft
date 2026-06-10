<style>
    /* Member panel — turn the "create account" link on the login page into a
       prominent, professional CTA button (green → gold gradient). */
    a[href*="/dashboard/register"] {
        display: inline-flex !important;
        align-items: center;
        gap: .4rem;
        margin-top: .7rem;
        padding: .6rem 1.6rem;
        border-radius: .85rem;
        background: linear-gradient(135deg, #006C35 0%, #C9A961 100%);
        color: #fff !important;
        font-weight: 800;
        letter-spacing: .2px;
        box-shadow: 0 6px 18px rgba(0, 108, 53, .35);
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        text-decoration: none !important;
    }

    a[href*="/dashboard/register"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 26px rgba(0, 108, 53, .45);
        filter: brightness(1.06);
        color: #fff !important;
    }

    /* Hide the sidebar scrollbar (scrolling still works) across the whole
       member panel — dashboard, profile, statistics, my files. */
    .fi-sidebar-nav::-webkit-scrollbar,
    .fi-sidebar::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }
    .fi-sidebar-nav,
    .fi-sidebar {
        scrollbar-width: none !important;      /* Firefox */
        -ms-overflow-style: none !important;   /* IE/Edge */
    }
</style>
