<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects gibberish / bot-generated display names (e.g. "UqOTgcNRMAgovYkoMBukn")
 * while accepting real names in Arabic or Latin scripts. Heuristic, not a
 * dictionary — it looks for the fingerprints of random strings:
 *   • scattered internal capitals (random CamelCase) — the strongest signal,
 *   • an implausible vowel ratio (walls of consonants, or all vowels),
 *   • long consonant runs, vowel-less words, mostly-digit input.
 *
 * Arabic (and other non-Latin) names skip the Latin-specific checks — bots use
 * random Latin strings, and Arabic letters have no "case" to abuse.
 */
class MeaningfulName implements ValidationRule
{
    private const VOWELS = 'aeiouyàâäáãåæéèêëíìîïóòôöõøúùûüýÿ';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        if ($raw === '') {
            return; // 'required' handles emptiness
        }

        // At least two letters — a real name isn't punctuation or a single glyph.
        $letters = preg_match_all('/\p{L}/u', $raw);
        if ($letters < 2) {
            $fail(__('profile.invalid_name'));

            return;
        }

        // Same letter repeated ("aaaaaa", "ككككك").
        if (preg_match('/^(\p{L})\1{3,}$/u', preg_replace('/[\s\-\'._]+/u', '', $raw) ?? $raw)) {
            $fail(__('profile.invalid_name'));

            return;
        }

        // Contains Arabic script → accept (bots here use Latin randomness).
        if (preg_match('/\p{Arabic}/u', $raw)) {
            return;
        }

        // Mostly digits is not a name.
        $digits = preg_match_all('/\p{Nd}/u', $raw);
        if ($digits > 0 && $digits / max($letters + $digits, 1) > 0.4) {
            $fail(__('profile.invalid_name'));

            return;
        }

        $tokens = preg_split('/[\s\-\'._]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [$raw];
        $internalCaps = 0;

        foreach ($tokens as $token) {
            $onlyLetters = preg_replace('/[^\p{L}]/u', '', $token) ?? '';
            if ($onlyLetters === '') {
                continue;
            }

            // Random CamelCase: uppercase letters that aren't the token's first char,
            // counted only for genuinely mixed-case tokens (ALLCAPS / Titlecase are fine).
            $isAllUpper = $onlyLetters === $this->upper($onlyLetters);
            $isAllLower = $onlyLetters === $this->lower($onlyLetters);
            if (! $isAllUpper && ! $isAllLower) {
                $chars = preg_split('//u', $onlyLetters, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($chars as $i => $ch) {
                    if ($i > 0 && $ch !== $this->lower($ch)) {
                        $internalCaps++;
                    }
                }
            }

            // ASCII-word checks (skip accented/other-script words to avoid false hits).
            $ascii = strtolower(preg_replace('/[^A-Za-z]/', '', $token) ?? '');
            if (strlen($ascii) >= 4) {
                $vowels = preg_match_all('/[aeiouy]/', $ascii);

                // A 4+ letter word with no vowel at all is gibberish ("qwrtp").
                if ($vowels === 0) {
                    $fail(__('profile.invalid_name'));

                    return;
                }
                // 5+ consonants in a row.
                if (preg_match('/[bcdfghjklmnpqrstvwxz]{5,}/', $ascii)) {
                    $fail(__('profile.invalid_name'));

                    return;
                }
            }
        }

        if ($internalCaps >= 2) {
            $fail(__('profile.invalid_name'));

            return;
        }

        // Overall vowel ratio across the Latin letters (catches consonant walls
        // and all-vowel strings that slipped past the per-word checks).
        $flat = mb_strtolower(preg_replace('/[^\p{L}]/u', '', $raw) ?? '');
        $ascii = preg_replace('/[^a-z'.self::VOWELS.']/u', '', $flat) ?? '';
        $asciiLen = mb_strlen($ascii);
        if ($asciiLen >= 5) {
            $vowelCount = preg_match_all('/['.self::VOWELS.']/u', $ascii);
            $ratio = $vowelCount / $asciiLen;
            if ($ratio < 0.12 || $ratio > 0.9) {
                $fail(__('profile.invalid_name'));
            }
        }
    }

    private function upper(string $v): string
    {
        return mb_strtoupper($v);
    }

    private function lower(string $v): string
    {
        return mb_strtolower($v);
    }
}
