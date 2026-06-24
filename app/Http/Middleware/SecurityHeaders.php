<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hardening response headers (clickjacking, MIME-sniffing, referrer, CSP).
 * Addresses the UpGuard "Website Security" findings. The CSP is intentionally
 * permissive (allows https: + inline/eval, which the Tailwind CDN, Alpine,
 * Livewire, model-viewer and AdSense all need) but PRESENT — it blocks framing
 * by other origins and auto-upgrades any http subresource to https.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'browsing-topics=(), interest-cohort=()');

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
                "style-src 'self' 'unsafe-inline' https:",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https:",
                "connect-src 'self' https:",
                "media-src 'self' blob: https:",
                "worker-src 'self' blob:",
                "frame-src 'self' https:",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                'upgrade-insecure-requests',
            ]));
        }

        return $response;
    }
}
