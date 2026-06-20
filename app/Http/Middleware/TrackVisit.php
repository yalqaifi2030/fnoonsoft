<?php

namespace App\Http\Middleware;

use App\Jobs\ResolveIpLocation;
use App\Models\Setting;
use App\Models\Visit;
use App\Support\UserAgent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a row in `visits` for every public HTML page view (country via the
 * Cloudflare header, city/region resolved async per IP, browser/OS/device from
 * the user agent). The DB write happens in terminate() so it never adds latency
 * to the response; the visitor cookie is set in handle() (queuing it later would
 * be too late). Everything is wrapped so analytics can never break a page.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $vid = $request->cookie('fn_vid');

            if (! $vid || ! preg_match('/^[a-f0-9]{32}$/', (string) $vid)) {
                $vid = bin2hex(random_bytes(16));
                // Queued here (in the "in" phase) so AddQueuedCookiesToResponse
                // attaches it to the outgoing response.
                Cookie::queue('fn_vid', $vid, 60 * 24 * 365, null, null, $request->isSecure(), true, false, 'Lax');
            }

            $request->attributes->set('fn_vid', $vid);
        } catch (\Throwable $e) {
            // never block the request over analytics
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldTrack($request, $response)) {
                return;
            }

            if (! Setting::get('analytics_enabled', true)) {
                return;
            }

            $info = UserAgent::parse($request->userAgent());
            $ip = $request->ip();

            $cc = strtoupper((string) $request->header('CF-IPCountry'));
            if (! preg_match('/^[A-Z]{2}$/', $cc) || in_array($cc, ['XX', 'T1', 'A1', 'A2'], true)) {
                $cc = null;
            }

            $city = null;
            $region = null;
            $loc = Cache::get('iploc:'.$ip);

            if (is_array($loc)) {
                $cc = $cc ?: ($loc['country'] ?? null);
                $city = $loc['city'] ?? null;
                $region = $loc['region'] ?? null;
            } elseif (! $info['is_bot']
                && $this->isPublicIp($ip)
                && Setting::get('analytics_geo_enabled', true)
                && Cache::add('iploc:lock:'.$ip, 1, 600)) {
                ResolveIpLocation::dispatch($ip);
            }

            Visit::create([
                'visitor_id' => $request->attributes->get('fn_vid'),
                'ip_address' => $ip,
                'country' => $cc,
                'region' => $region,
                'city' => $city,
                'browser' => $info['browser'],
                'browser_version' => $info['browser_version'],
                'os' => $info['os'],
                'device' => $info['device'],
                'is_bot' => $info['is_bot'],
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
                'referer_host' => $this->refererHost($request),
                'user_id' => optional($request->user())->id,
            ]);
        } catch (\Throwable $e) {
            // analytics must never break a request
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }
        if ($request->ajax() || $request->pjax() || $request->expectsJson()) {
            return false;
        }
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $ct = (string) $response->headers->get('Content-Type');
        if ($ct !== '' && ! str_contains($ct, 'text/html')) {
            return false;
        }

        $path = ltrim($request->path(), '/');
        if ($path === '') {
            return true; // home page
        }

        $skip = ['admin', 'upload', 'dashboard', 'livewire', 'download', 'go', 'api',
            'build', 'storage', 'vendor', 'telescope', 'horizon',
            'robots.txt', 'favicon.ico', 'ads.txt', 'up', 'login', 'register'];

        foreach ($skip as $p) {
            if ($path === $p || str_starts_with($path, $p.'/')) {
                return false;
            }
        }

        if (preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|map|xml|txt|json|mp4|webm|pdf)$/i', $path)) {
            return false;
        }

        return true;
    }

    private function refererHost(Request $request): ?string
    {
        $ref = $request->headers->get('referer');
        if (! $ref) {
            return null;
        }

        $host = parse_url($ref, PHP_URL_HOST);
        if (! $host || $host === $request->getHost()) {
            return null; // internal navigation
        }

        return mb_substr($host, 0, 120);
    }

    private function isPublicIp(?string $ip): bool
    {
        return $ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
