<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks reserved / impersonation names on public registration + member profile
 * (display name and @username). "Contains" keywords are rejected even as a
 * substring (so admin123 / fnoon-support / superadmin are caught); "exact"
 * words (routes + generic handles) are rejected only on a full match.
 * Arabic and English, diacritic/separator-insensitive. Staff are exempted at
 * the call site (their legitimate names may contain these words).
 */
class ReservedName implements ValidationRule
{
    /** Impersonation keywords — rejected even as a substring. */
    public const CONTAINS = [
        'admin', 'administrator', 'superadmin', 'sysadmin', 'webmaster',
        'moderator', 'support', 'official', 'staff', 'helpdesk',
        'fnoon', 'finunsoft', 'finunksa',
        // Arabic (compared after normalisation)
        'مدير', 'ادمن', 'اداره', 'المشرف', 'مشرف', 'الدعم', 'دعم',
        'الرسمي', 'رسمي', 'الموقع', 'الفريق', 'موظف',
    ];

    /** Reserved exact tokens — routes + generic roles (full-match only). */
    public const EXACT = [
        'upload', 'dashboard', 'api', 'login', 'register', 'logout', 'u', 'd', 'go',
        'download', 'me', 'settings', 'search', 'browse', 'blog', 'contact', 'info',
        'help', 'mod', 'dev', 'system', 'owner', 'root', 'security', 'team', 'about',
        'النظام', 'الامن', 'المالك', 'تواصل',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $compact = $this->normalize((string) $value);

        if ($compact === '') {
            return; // emptiness is handled by other (required) rules
        }

        foreach (self::CONTAINS as $kw) {
            if (str_contains($compact, $this->normalize($kw))) {
                $fail(__('profile.reserved_name'));

                return;
            }
        }

        foreach (self::EXACT as $word) {
            if ($compact === $this->normalize($word)) {
                $fail(__('profile.reserved_name'));

                return;
            }
        }
    }

    /** Lowercase, unify Arabic letters, drop diacritics, strip non-alphanumerics. */
    private function normalize(string $v): string
    {
        $v = mb_strtolower(trim($v));
        $v = str_replace(['أ', 'إ', 'آ', 'ٱ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ا', 'ه', 'ي'], $v);
        $v = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0640}]/u', '', $v) ?? $v;

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $v) ?? '';
    }
}
