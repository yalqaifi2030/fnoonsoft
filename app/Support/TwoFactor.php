<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Login-challenge state for two-factor auth.
 *
 *  • "passed" is bound to the user id (a bare `true` survived a re-login as a
 *    different account in the same session → 2FA bypass) and is cleared on
 *    every Login event.
 *  • Codes: 5 wrong tries per 15 min per user, then locked; an accepted TOTP
 *    step can't be replayed; recovery codes compared in constant time.
 */
class TwoFactor
{
    private const KEY = '2fa_passed';

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 900;

    public static function passed(User $user): bool
    {
        return (string) session(self::KEY) === (string) $user->getKey();
    }

    public static function markPassed(User $user): void
    {
        session()->put(self::KEY, (string) $user->getKey());
    }

    public static function forget(): void
    {
        session()->forget(self::KEY);
    }

    /** Seconds until the user may try again, or 0 when not locked. */
    public static function lockedFor(User $user): int
    {
        $key = self::limiterKey($user);

        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS) ? RateLimiter::availableIn($key) : 0;
    }

    /** Check an authenticator or recovery code; counts failures towards the lock. */
    public static function attempt(User $user, string $code): bool
    {
        if (self::lockedFor($user) > 0) {
            return false;
        }

        if (self::verifyTotp($user, $code) || self::useRecoveryCode($user, $code)) {
            RateLimiter::clear(self::limiterKey($user));

            return true;
        }

        RateLimiter::hit(self::limiterKey($user), self::DECAY_SECONDS);

        return false;
    }

    private static function verifyTotp(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $counter = Totp::match($user->two_factor_secret, $code);
        if ($counter === null) {
            return false;
        }

        // Each 30s step is single-use: a sniffed/shoulder-surfed code can't be replayed.
        $lastKey = '2fa:last:'.$user->getKey();
        if ($counter <= (int) Cache::get($lastKey, -1)) {
            return false;
        }
        Cache::put($lastKey, $counter, 300);

        return true;
    }

    private static function useRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim($code)); // stored upper-cased
        $codes = array_values((array) ($user->two_factor_recovery_codes ?? []));

        foreach ($codes as $i => $stored) {
            if (is_string($stored) && hash_equals($stored, $code)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private static function limiterKey(User $user): string
    {
        return '2fa-attempts:'.$user->getKey();
    }
}
