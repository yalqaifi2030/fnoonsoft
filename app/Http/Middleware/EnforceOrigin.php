<?php

namespace App\Http\Middleware;

use App\Support\Security;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Origin protection — the first line of defence. Runs before everything else.
 *
 *  • Host allowlist: a request whose Host header isn't one of ours (e.g. the raw
 *    server IP, as used in the "62.72.0.162/admin/login" probe) is refused.
 *  • Optional Cloudflare enforcement: when the domain is proxied by Cloudflare,
 *    reject anything that reached the origin directly (no CF-Ray header).
 *
 * Internal/loopback IPs and the /up health check are always exempt so local
 * tooling, cron and monitoring keep working. Fails open on any error.
 */
class EnforceOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! config('security.enforce_origin')) {
                return $next($request);
            }

            $ip = $request->ip();
            if ($request->is('up') || $this->isInternal($ip)) {
                return $next($request);
            }

            // 1) Host must be one of ours (blocks raw-IP / unknown-host access).
            //    No legitimate client ever sends a wrong Host → block on sight.
            $host = strtolower(trim($request->getHost()));
            $allowed = array_map('strtolower', (array) config('security.allowed_hosts'));
            if ($allowed && ! in_array($host, $allowed, true)) {
                return $this->deny($request, 'bad host: '.$host, 'critical');
            }

            // 2) Optionally require the request to have come through Cloudflare.
            //    Softer (high) — a paused Cloudflare shouldn't permaban real users.
            if (config('security.require_cloudflare')
                && ! $request->headers->has('cf-ray')
                && ! $request->headers->has('cf-connecting-ip')) {
                return $this->deny($request, 'direct origin (no Cloudflare)', 'high');
            }
        } catch (\Throwable $e) {
            // Security must never take the site down — fail open.
        }

        return $next($request);
    }

    private function deny(Request $request, string $detail, string $severity = 'high'): Response
    {
        try {
            Security::flag($request, 'origin', $severity, $detail);
        } catch (\Throwable $e) {
            // best-effort logging
        }

        return response()->view('security-blocked', [], 403);
    }

    /** Loopback or private/reserved (non-public) address → exempt. */
    private function isInternal(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return true;
        }
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
