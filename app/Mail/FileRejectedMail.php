<?php

namespace App\Mail;

use App\Models\Asset;
use Illuminate\Mail\Mailable;

/** Tells a member one of their files was removed by moderation, and why. */
class FileRejectedMail extends Mailable
{
    public function __construct(public Asset $asset, public string $reasonLabel) {}

    public function build(): self
    {
        $body = '<p>'.e(__('moderation.mail.body', ['file' => $this->asset->original_name])).'</p>'
            .'<p><strong>'.e(__('moderation.mail.reason')).':</strong> '.e($this->reasonLabel).'</p>'
            .($this->asset->rejection_note ? '<p>'.nl2br(e($this->asset->rejection_note)).'</p>' : '')
            .'<p>'.e(__('moderation.mail.appeal')).'</p>';

        return $this->subject(__('moderation.mail.subject'))
            ->view('emails.branded')
            ->with([
                'subject' => __('moderation.mail.subject'),
                'preheader' => __('moderation.mail.body', ['file' => $this->asset->original_name]),
                'heading' => __('moderation.mail.heading'),
                'bodyHtml' => $body,
                'buttonUrl' => url('/dashboard/support-tickets/create'),
                'buttonLabel' => __('moderation.mail.cta'),
                'footer' => e(__('moderation.mail.footer')),
            ]);
    }
}
