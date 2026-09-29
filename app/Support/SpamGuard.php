<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\ContactBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Contact-form spam protection, in layers:
 *
 *  1. Bot checks (hard, silent drop): honeypot field, a signed timestamp token
 *     (missing/forged/too fast = a script, not a person), and a JS proof that
 *     only a real browser fills in on submit.
 *  2. Block list: email, whole domain, or IP — silent drop.
 *  3. Content score: links, known ad/SEO/"your price" phrases (multi-language),
 *     bot-style names, foreign scripts, repeat or previously-spammy senders.
 *     Score >= THRESHOLD → saved to the Spam folder instead of the inbox.
 *
 * Bots are always told "sent" so they learn nothing about what tripped them.
 */
class SpamGuard
{
    public const THRESHOLD = 5;

    /** Seconds a human needs at minimum to fill the form. */
    private const MIN_SECONDS = 3;

    /** [regex, points, reason] — matched against name + subject + message (lower-cased). */
    private const PATTERNS = [
        // "I wanted to know your price" bots, in many languages.
        ['/\b(your|ur)\s+(price|pricing|rates?)\b|wanted to know (your|the) price/u', 4, 'price_bot'],
        ['/quer(í|i)a saber (tu|o seu|su|el) (precio|prezo|preço)|verð þitt|meg akartam tudni az árát|ihren preis|deinen preis|votre prix|uw prijs|je prijs|vaši cenu|vaš cjenik|il vostro prezzo|il tuo prezzo|fiyatınızı|prețul|hintanne|ceny|цену|цена/u', 5, 'price_bot'],
        // SEO / link selling.
        ['/back\s?links?|link (exchange|building|insertion)|exchange (links|backlinks)|place (links|your link)|guest post|sponsored post|do-?follow/u', 4, 'seo_offer'],
        ['/\bseo\b|domain authority|\b(da|dr)\s?\d{2}|first page of google|rank (higher|#?1|first)|organic traffic|website traffic|increase (your )?traffic/u', 3, 'seo_offer'],
        ['/collab idea|quick win|strike when the iron|hello .{0,40} owner|dear (website|site) owner|i came across your (website|site)/u', 3, 'marketing'],
        // Classic spam topics.
        ['/crypto|bitcoin|forex|casino|betting|\bloan\b|viagra|cialis|porn|escort|adult dating|investment opportunity/u', 5, 'spam_topic'],
        ['/whats\s?app.{0,20}\+?\d{7,}|telegram.{0,10}@\w+/u', 3, 'contact_bait'],
        ['/unsubscribe|opt[- ]out|to stop receiving/u', 2, 'bulk_mail'],
    ];

    // --- Layer 1: bot checks ---------------------------------------------

    /** Signed, time-stamped token rendered into the form. */
    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** The JS proof a browser derives from the token on submit (see contact.blade.php). */
    public static function jsProof(string $token): string
    {
        return strrev(substr($token, -12));
    }

    /** Reason key if the submission is certainly automated, else null. */
    public static function botCheck(Request $request): ?string
    {
        if ($request->filled('website')) {
            return 'honeypot';
        }

        $token = (string) $request->input('_ft', '');
        try {
            $issued = (int) Crypt::decryptString($token);
        } catch (\Throwable $e) {
            return 'no_token';
        }

        if (time() - $issued < self::MIN_SECONDS) {
            return 'too_fast';
        }
        if (! hash_equals(self::jsProof($token), (string) $request->input('_js', ''))) {
            return 'no_js';
        }

        return null;
    }

    // --- Layer 2: block list ---------------------------------------------

    public static function blocked(string $email, ?string $ip): ?ContactBlock
    {
        $email = strtolower(trim($email));
        $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';

        $block = ContactBlock::query()
            ->where(fn ($q) => $q->where(['type' => 'email', 'value' => $email])
                ->orWhere(fn ($q) => $q->where('type', 'domain')->where('value', $domain))
                ->orWhere(fn ($q) => $q->where('type', 'ip')->where('value', (string) $ip)))
            ->first();

        if ($block) {
            $block->forceFill(['hits' => $block->hits + 1, 'last_hit_at' => now()])->save();
        }

        return $block;
    }

    // --- Layer 3: content score -------------------------------------------

    /**
     * @param  array{name?:string,email?:string,subject?:?string,message?:string}  $data
     * @return array{0:int, 1:array<int,string>} score and reason keys
     */
    public static function score(array $data, ?string $ip = null, ?int $ignoreId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $text = mb_strtolower($name.' '.($data['subject'] ?? '').' '.($data['message'] ?? ''));
        $message = (string) ($data['message'] ?? '');

        $score = 0;
        $reasons = [];
        $add = function (int $points, string $reason) use (&$score, &$reasons) {
            $score += $points;
            $reasons[] = $reason;
        };

        // Links.
        $links = preg_match_all('~https?://|www\.|\b[a-z0-9-]+\.(com|net|org|io|xyz|top|site|online|shop)/~i', $message);
        if ($links >= 3) {
            $add(5, 'many_links');
        } elseif ($links >= 1) {
            $add(2, 'has_link');
        }
        if (preg_match('~bit\.ly|tinyurl|t\.co/|goo\.gl|cutt\.ly|rb\.gy~i', $message)) {
            $add(3, 'short_link');
        }

        // Phrases (each reason counted once).
        $seen = [];
        foreach (self::PATTERNS as [$rx, $points, $reason]) {
            if (! isset($seen[$reason]) && preg_match($rx, $text)) {
                $add($points, $reason);
                $seen[$reason] = true;
            }
        }

        // Bot-style names: "RobertHiemo" (two capitalised words glued), or a URL.
        if (preg_match('/^[A-Z][a-z]{2,}[A-Z][a-z]{2,}$/', $name)) {
            $add(3, 'bot_name');
        }
        if (preg_match('~https?://|www\.|\.com\b~i', $name)) {
            $add(5, 'link_in_name');
        }

        // Scripts/languages the site doesn't serve (Cyrillic, CJK, …).
        if (preg_match('/[\x{0400}-\x{04FF}\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}\x{0E00}-\x{0E7F}]/u', $text)) {
            $add(3, 'foreign_script');
        }

        // Sender history.
        $history = Contact::query()->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));
        if ($email !== '' && (clone $history)->where('email', $email)->where('is_spam', true)->exists()) {
            $add(5, 'known_spammer');
        }
        $recent = (clone $history)->where('created_at', '>=', now()->subDay())
            ->where(fn ($q) => $q->where('email', $email)->when($ip, fn ($q) => $q->orWhere('ip_address', $ip)))
            ->count();
        if ($recent >= 2) {
            $add(2, 'repeat_sender');
        }

        return [$score, array_values(array_unique($reasons))];
    }
}
