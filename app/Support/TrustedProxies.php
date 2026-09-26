<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * The only peers allowed to speak for the client via X-Forwarded-* /
 * CF-Connecting-IP. Trusting '*' let anyone spoof their IP (XFF) and the
 * request host (X-Forwarded-Host → poisoned password-reset links), because
 * Cloudflare passes those client-supplied headers straight through.
 *
 * List = Cloudflare edge ranges (https://www.cloudflare.com/ips) + loopback +
 * private networks (local Docker/Coolify proxies). Mirrors the nginx origin
 * lock on the server — keep both in sync when Cloudflare publishes changes.
 */
class TrustedProxies
{
    public const CLOUDFLARE = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public const LOCAL = [
        '127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7',
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        return array_merge(self::CLOUDFLARE, self::LOCAL);
    }

    /** Is the directly-connected peer one of our trusted proxies? */
    public static function isTrusted(?string $ip): bool
    {
        return $ip !== null && $ip !== '' && IpUtils::checkIp($ip, self::all());
    }
}
