<?php

namespace App\Support;

use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The brain behind SecurityGuard: turns detections into logged events, a rolling
 * per-IP threat score, and — past a threshold — an automatic block. Deliberately
 * cautious (the "balanced" policy): a single confirmed attack blocks instantly,
 * weaker signals only block if they keep happening, so real visitors are safe.
 */
class Security
{
    /** Points per severity; blocks once an IP reaches THRESHOLD within WINDOW. */
    private const WEIGHTS = ['critical' => 100, 'high' => 50, 'medium' => 25, 'low' => 15];

    private const THRESHOLD = 100;

    private const WINDOW_MIN = 15;

    private const AUTO_BLOCK_DAYS = 7;

    /** Is this IP currently blocked? (cached list, refreshed every 60s) */
    public static function isBlocked(string $ip): bool
    {
        $list = Cache::remember('sec:blocked', 60, fn () => BlockedIp::active()->pluck('ip')->all());

        return in_array($ip, $list, true);
    }

    /**
     * Handle one request's detections: log the top one, add to the IP's score,
     * and block if the threshold is reached. Returns true if the IP got blocked.
     *
     * @param  array<int, array{type:string, severity:string, detail:string}>  $detections
     */
    public static function handle(Request $request, array $detections): bool
    {
        if (! $detections) {
            return false;
        }

        // The most severe detection drives the response.
        $order = ['critical' => 3, 'high' => 2, 'medium' => 1, 'low' => 0];
        usort($detections, fn ($a, $b) => ($order[$b['severity']] ?? 0) <=> ($order[$a['severity']] ?? 0));
        $top = $detections[0];

        $ip = $request->ip();
        $weight = self::WEIGHTS[$top['severity']] ?? 10;
        $score = self::addScore($ip, $weight);
        $willBlock = $top['severity'] === 'critical' || $score >= self::THRESHOLD;

        $types = implode(',', array_values(array_unique(array_map(fn ($d) => $d['type'], $detections))));

        self::log([
            'ip' => $ip,
            'type' => $top['type'],
            'severity' => $top['severity'],
            'method' => $request->method(),
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 1000),
            'detail' => mb_substr('['.$types.'] '.$top['detail'], 0, 1000),
            'user_id' => $request->user()?->id,
            'country' => self::country($request),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'blocked' => $willBlock,
        ]);

        if ($willBlock && self::block($ip, $top['type'], __('security_admin.reason_auto', ['type' => $top['type']]))) {
            self::alertStaff($ip, $top);

            return true;
        }

        return false;
    }

    /**
     * Log an arbitrary security signal (e.g. origin/host violations) and, past
     * the threshold, auto-block the IP. Returns true if the IP got blocked.
     */
    public static function flag(Request $request, string $type, string $severity, string $detail): bool
    {
        $ip = $request->ip();
        if ($ip === null) {
            return false;
        }

        $weight = self::WEIGHTS[$severity] ?? 25;
        $score = self::addScore($ip, $weight);
        $willBlock = $severity === 'critical' || $score >= self::THRESHOLD;

        self::log([
            'ip' => $ip,
            'type' => $type,
            'severity' => $severity,
            'method' => $request->method(),
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 1000),
            'detail' => mb_substr($detail, 0, 1000),
            'user_id' => $request->user()?->id,
            'country' => self::country($request),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'blocked' => $willBlock,
        ]);

        if ($willBlock && self::block($ip, $type, __('security_admin.reason_auto', ['type' => $type]))) {
            self::alertStaff($ip, ['type' => $type, 'severity' => $severity, 'detail' => $detail]);

            return true;
        }

        return false;
    }

    /** Brute-force guard: called on every failed login attempt. */
    public static function flagFailedLogin(string $ip, ?string $email = null): void
    {
        if (self::isBlocked($ip)) {
            return;
        }

        $score = self::addScore($ip, self::WEIGHTS['low']);

        self::log([
            'ip' => $ip,
            'type' => 'bruteforce',
            'severity' => 'low',
            'method' => 'POST',
            'path' => 'login',
            'detail' => 'failed login'.($email ? ': '.mb_substr($email, 0, 120) : ''),
            'blocked' => $score >= self::THRESHOLD,
        ]);

        if ($score >= self::THRESHOLD) {
            // Short (1h) block for login failures — protects against fat-fingering
            // admins locking themselves out, while still stopping a real attacker.
            self::block($ip, 'bruteforce', __('security_admin.reason_bruteforce'), auto: true, minutes: 60);
        }
    }

    /** Add/refresh this IP's rolling score; returns the running total. */
    private static function addScore(string $ip, int $weight): int
    {
        $key = 'sec:score:'.$ip;
        Cache::add($key, 0, now()->addMinutes(self::WINDOW_MIN));

        return (int) Cache::increment($key, $weight);
    }

    /**
     * Create or refresh a block (never downgrades a permanent/manual one).
     * Auto blocks skip private/reserved IPs so the owner, LAN and health checks
     * can never be locked out. Returns true only if an IP is actually blocked.
     */
    public static function block(string $ip, ?string $type, ?string $reason, bool $auto = true, ?int $minutes = null): bool
    {
        // Never auto-block internal / non-public addresses.
        if ($auto && ! self::isPublicIp($ip)) {
            return false;
        }

        $expires = $auto
            ? ($minutes !== null ? now()->addMinutes($minutes) : now()->addDays(self::AUTO_BLOCK_DAYS))
            : null;

        $existing = BlockedIp::where('ip', $ip)->first();

        if ($existing) {
            $existing->increment('hits');
            // Auto blocks: a repeat offender (3rd strike) becomes PERMANENT;
            // otherwise just extend the window. Manual/permanent blocks untouched.
            if ($auto && $existing->auto && $existing->expires_at !== null) {
                $existing->update(['expires_at' => $existing->hits >= 3 ? null : $expires]);
            }
        } else {
            BlockedIp::create([
                'ip' => $ip,
                'reason' => $reason,
                'type' => $type,
                'auto' => $auto,
                'hits' => 1,
                'expires_at' => $expires,
            ]);
        }

        Cache::forget('sec:blocked');

        return true;
    }

    /** Public, routable IP? (excludes localhost, LAN, reserved ranges) */
    private static function isPublicIp(?string $ip): bool
    {
        return $ip !== null && filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private static function log(array $data): void
    {
        try {
            SecurityEvent::create($data);
        } catch (\Throwable $e) {
            // security logging must never break the request
        }
    }

    /** Notify staff on a block — throttled to once per IP per hour to avoid spam. */
    private static function alertStaff(string $ip, array $top): void
    {
        if (! Cache::add('sec:alert:'.$ip, 1, 3600)) {
            return;
        }

        try {
            $staff = User::whereHas('roles')->get();
            if ($staff->isEmpty()) {
                return;
            }

            Notification::make()
                ->title(__('security_admin.alert_title'))
                ->body(__('security_admin.alert_body', ['type' => $top['type'], 'ip' => $ip]))
                ->icon('heroicon-o-shield-exclamation')
                ->danger()
                ->actions([
                    Action::make('view')
                        ->label(__('security_admin.alert_open'))
                        ->url(route('filament.admin.resources.security-events.index'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($staff);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    private static function country(Request $request): ?string
    {
        $cc = strtoupper((string) $request->header('CF-IPCountry'));

        return preg_match('/^[A-Z]{2}$/', $cc) && ! in_array($cc, ['XX', 'T1', 'A1', 'A2'], true) ? $cc : null;
    }
}
