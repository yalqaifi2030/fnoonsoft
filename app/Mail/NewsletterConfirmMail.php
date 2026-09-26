<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * Double opt-in: "confirm your subscription" (branded layout). Nobody gets the
 * newsletter until the address owner clicks the link — so the form can't be
 * used to sign strangers up.
 */
class NewsletterConfirmMail extends Mailable
{
    public function __construct(public string $confirmUrl) {}

    public function build(): self
    {
        return $this->subject(__('newsletter.confirm_subject'))
            ->view('emails.branded')
            ->with([
                'subject' => __('newsletter.confirm_subject'),
                'preheader' => __('newsletter.confirm_body'),
                'heading' => __('newsletter.confirm_heading'),
                'bodyHtml' => '<p>'.e(__('newsletter.confirm_body')).'</p>',
                'buttonUrl' => $this->confirmUrl,
                'buttonLabel' => __('newsletter.confirm_cta'),
                'footer' => e(__('newsletter.confirm_ignore')),
            ]);
    }
}
