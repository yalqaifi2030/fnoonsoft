<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Admin-controlled, branded transactional email templates. Each template has a
 * subject + heading + body + button label + footer (admin-editable, lang-backed
 * defaults), rendered through the shared resources/views/emails/branded layout.
 */
class MailTemplate
{
    public const KEYS = ['verify', 'reset', 'welcome'];

    public const FIELDS = ['subject', 'heading', 'body', 'button_label', 'footer'];

    /** Placeholders offered per template (shown in the admin UI). */
    public const PLACEHOLDERS = [
        'verify' => ['{name}', '{site_name}'],
        'reset' => ['{name}', '{site_name}', '{minutes}'],
        'welcome' => ['{name}', '{site_name}'],
    ];

    public static function default(string $tpl, string $field): string
    {
        return (string) __("mailtpl.defaults.{$tpl}.{$field}");
    }

    /** Admin-saved value, or the lang default. */
    public static function field(string $tpl, string $field): string
    {
        $v = Setting::get("mail_tpl_{$tpl}_{$field}");

        return filled($v) ? (string) $v : self::default($tpl, $field);
    }

    /** verify + reset are essential (always on); welcome is toggleable. */
    public static function enabled(string $tpl): bool
    {
        if ($tpl !== 'welcome') {
            return true;
        }

        return (bool) Setting::get('mail_tpl_welcome_enabled', true);
    }

    /** @return array<string,string> all fields resolved (admin value or default) */
    public static function resolved(string $tpl): array
    {
        $out = [];
        foreach (self::FIELDS as $f) {
            $out[$f] = self::field($tpl, $f);
        }

        return $out;
    }

    private static function replace(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{'.$k.'}', e((string) $v), $text);
        }

        return $text;
    }

    /** Build the branded view data from explicit fields (preview) or settings. */
    public static function viewData(string $tpl, array $vars, ?string $buttonUrl, ?array $fields = null): array
    {
        $fields ??= self::resolved($tpl);
        $vars['site_name'] = $vars['site_name'] ?? Setting::text('site_name', config('app.name'));

        $heading = self::replace($fields['heading'] ?? '', $vars);

        return [
            'subject' => self::replace($fields['subject'] ?? '', $vars),
            'preheader' => trim(strip_tags($heading)),
            'heading' => $heading,
            'bodyHtml' => nl2br(self::replace($fields['body'] ?? '', $vars)),
            'buttonLabel' => self::replace($fields['button_label'] ?? '', $vars),
            'buttonUrl' => $buttonUrl,
            'footer' => nl2br(self::replace($fields['footer'] ?? '', $vars)),
        ];
    }

    /** A ready MailMessage that renders the branded template. */
    public static function mailMessage(string $tpl, array $vars, ?string $buttonUrl = null): MailMessage
    {
        $data = self::viewData($tpl, $vars, $buttonUrl);

        return (new MailMessage)
            ->subject($data['subject'])
            ->view('emails.branded', $data);
    }

    /** Rendered HTML for the admin live preview (uses the given unsaved fields). */
    public static function renderHtml(string $tpl, array $fields, ?string $buttonUrl = null): string
    {
        return view('emails.branded', self::viewData($tpl, self::sampleVars($tpl), $buttonUrl ?: '#', $fields))->render();
    }

    /** Demo variables for previews / test sends. */
    public static function sampleVars(string $tpl): array
    {
        return [
            'name' => __('mailtpl.sample_name'),
            'site_name' => Setting::text('site_name', config('app.name')),
            'minutes' => 60,
        ];
    }
}
