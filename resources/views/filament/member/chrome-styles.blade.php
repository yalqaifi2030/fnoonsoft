<style>
    /* Member panel — a classic, elegant "create account" button on the login page:
       solid green with a fine gold border, square-ish corners, subtle shadow. */
    a[href*="/dashboard/register"] {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        margin-top: .55rem;
        padding: .55rem 1.7rem;
        border-radius: .5rem;
        background: transparent;
        color: #a8842c !important;
        border: 1.5px solid #C9A961;
        font-weight: 700;
        letter-spacing: .3px;
        transition: background .2s ease, color .2s ease, box-shadow .2s ease, transform .15s ease;
        text-decoration: none !important;
    }

    a[href*="/dashboard/register"]:hover {
        background: #C9A961;
        color: #fff !important;
        box-shadow: 0 5px 14px rgba(201, 169, 97, .3);
        transform: translateY(-1px);
    }

    /* Classic polish for the auth card (login / register):
       a fine gold border, a soft green shadow, and a thin gold-green top rule. */
    .fi-simple-main {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(201, 169, 97, .28) !important;
        border-radius: 1rem !important;
        box-shadow: 0 22px 55px -28px rgba(0, 108, 53, .35) !important;
    }

    .fi-simple-main::before {
        content: '';
        position: absolute;
        top: 0;
        inset-inline: 0;
        height: 3px;
        background: linear-gradient(90deg, #006C35, #C9A961, #006C35);
    }
</style>
