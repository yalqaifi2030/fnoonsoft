<?php

namespace App\Http\Middleware;

use App\Support\Security;
use App\Support\ThreatInspector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App-layer intrusion detection + auto-block (complements the Cloudflare WAF).
 * Blocks IPs that trip the threat threshold, records every attack signature for
 * the admin Security console, and ties events to signed-in members. Staff are
 * never inspected or blocked. A direct 403 Response is returned for blocked IPs
 * so the friendly 403→home redirect in bootstrap/app.php can't let them through.
 */
class SecurityGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Staff are exempt — their legitimate content can resemble payloads.
            if ($request->user()?->isStaff()) {
                return $next($request);
            }

            $ip = $request->ip();

            if ($ip && Security::isBlocked($ip)) {
                return $this->blocked();
            }

            $detections = ThreatInspector::inspect($request);
            if ($detections && Security::handle($request, $detections)) {
                return $this->blocked();
            }
        } catch (\Throwable $e) {
            // The security layer must never take the site down — fail open.
        }

        return $next($request);
    }

    private function blocked(): Response
    {
        return response()->view('security-blocked', [], 403);
    }
}
