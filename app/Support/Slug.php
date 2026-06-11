<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Build a URL slug that also works for Arabic (and any non-Latin) text.
 *
 * Str::slug() strips Arabic characters and returns an empty string, which is
 * why auto-slug fields looked "broken" for Arabic names. Here we try the normal
 * Latin slug first, then fall back to a Unicode-aware slug that keeps Arabic
 * letters/numbers and turns everything else into dashes (e.g. "برامج التصميم"
 * → "برامج-التصميم"). Arabic slugs are valid in URLs (percent-encoded).
 */
class Slug
{
    public static function make(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Non-Latin (e.g. Arabic) names keep their own script so the slug stays
        // readable: "برامج التصميم" → "برامج-التصميم" (valid in URLs, percent-
        // encoded). Pure-Latin names use the normal ASCII slug.
        if (preg_match('/[^\x00-\x7F]/', $value)) {
            // Drop combining marks (Arabic tashkeel) so "محرّر" → "محرر".
            $value = preg_replace('/\p{M}+/u', '', $value) ?? $value;
            $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? '';

            return trim(mb_strtolower($slug), '-');
        }

        return Str::slug($value);
    }
}
