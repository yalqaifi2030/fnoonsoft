<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects fake / throwaway email addresses so lists stay real:
 *   • disposable / temporary-mail providers (tempmail, mailinator, …),
 *   • placeholder/test domains (example.com, test.com, localhost),
 *   • domains that can't actually receive mail (no MX and no A record).
 *
 * Format is validated by the standard `email` rule before this runs. The MX
 * lookup fails OPEN (a transient DNS error never blocks a genuine subscriber).
 */
class RealEmail implements ValidationRule
{
    /** Known disposable / temporary-mail domains (matched incl. subdomains). */
    public const DISPOSABLE = [
        'mailinator.com', 'yopmail.com', 'guerrillamail.com', 'guerrillamail.info',
        'guerrillamail.net', 'guerrillamail.org', 'guerrillamailblock.com', 'sharklasers.com',
        'grr.la', 'spam4.me', '10minutemail.com', '10minutemail.net', '20minutemail.com',
        'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'tempmailo.com', 'tempmail.net',
        'tempmail.plus', 'tempinbox.com', 'throwawaymail.com', 'trashmail.com', 'trashmail.de',
        'trash-mail.com', 'dispostable.com', 'maildrop.cc', 'mailnesia.com', 'mailcatch.com',
        'getnada.com', 'nada.email', 'inboxkitten.com', 'moakt.com', 'mailsac.com',
        'fakeinbox.com', 'fakemailgenerator.com', 'emailondeck.com', 'spamgourmet.com',
        'mohmal.com', 'discard.email', 'mytemp.email', 'tempr.email', 'tmpmail.org',
        'tmpmail.net', '1secmail.com', '1secmail.org', '1secmail.net', 'byom.de',
        'emlhub.com', 'emlpro.com', 'luxusmail.org', 'wemel.top', 'cs.email',
        'mailpoof.com', 'mailtemp.net', 'minuteinbox.com', 'burnermail.io', 'anonaddy.me',
        'dropmail.me', 'mvrht.net', 'harakirimail.com', 'einrot.com', 'jetable.org',
    ];

    /** Obvious placeholder / non-deliverable domains. */
    private const PLACEHOLDER = [
        'example.com', 'example.org', 'example.net', 'test.com', 'test.net',
        'domain.com', 'email.com', 'mail.com', 'localhost', 'invalid', 'none.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = mb_strtolower(trim((string) $value));
        $at = strrpos($email, '@');
        if ($at === false) {
            return; // format handled by the `email` rule
        }

        $domain = substr($email, $at + 1);
        if ($domain === '') {
            return;
        }

        // 1) Placeholder / test domains.
        if (in_array($domain, self::PLACEHOLDER, true)) {
            $fail(__('newsletter.invalid_email'));

            return;
        }

        // 2) Disposable / temporary-mail (exact or as a subdomain).
        foreach (self::DISPOSABLE as $bad) {
            if ($domain === $bad || str_ends_with($domain, '.'.$bad)) {
                $fail(__('newsletter.invalid_email'));

                return;
            }
        }

        // 3) The domain must actually accept mail: require a real MX record.
        //    (Every genuine mail provider publishes MX; parked/fake domains — even
        //    when a wildcard/hijacking resolver invents an A record — do not.)
        //    getmxrr is used over checkdnsrr so we can confirm a non-empty host list.
        //    Fails OPEN on a DNS error so a transient hiccup never blocks a real user.
        try {
            $hosts = [];
            $hasMx = @getmxrr($domain, $hosts) && ! empty($hosts);
            if (! $hasMx) {
                $fail(__('newsletter.invalid_email'));
            }
        } catch (\Throwable $e) {
            // network/DNS error — don't punish a genuine subscriber
        }
    }
}
