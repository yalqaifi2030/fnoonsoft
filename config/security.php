<?php

$appHost = parse_url((string) env('APP_URL'), PHP_URL_HOST) ?: '';

return [

    /*
    |--------------------------------------------------------------------------
    | Origin protection
    |--------------------------------------------------------------------------
    | enforce_origin   — reject requests whose Host isn't one of ours (blocks
    |                    raw-IP access like https://<server-ip>/admin/login).
    | require_cloudflare — additionally reject any request that didn't come
    |                    through Cloudflare (no CF-Ray/CF-Connecting-IP header).
    |                    ⚠ Enable ONLY when the domain is actually proxied by
    |                    Cloudflare, otherwise every request is denied.
    | allowed_hosts    — the hostnames the app answers on.
    */

    'enforce_origin' => (bool) env('SECURITY_ENFORCE_ORIGIN', false),

    'require_cloudflare' => (bool) env('SECURITY_REQUIRE_CLOUDFLARE', false),

    'allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        env('SECURITY_ALLOWED_HOSTS')
            ? explode(',', (string) env('SECURITY_ALLOWED_HOSTS'))
            : array_filter([$appHost, $appHost ? 'www.'.$appHost : null])
    ))),

];
