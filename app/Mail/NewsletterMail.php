<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * One newsletter email, rendered with the shared branded layout
 * (resources/views/emails/branded.blade.php). Includes a one-click
 * unsubscribe link (footer + List-Unsubscribe header) for deliverability.
 */
class NewsletterMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public string $bodyHtml,
        public ?string $ctaUrl,
        public ?string $ctaLabel,
        public string $unsubscribeUrl,
    ) {}

    public function build(): self
    {
        $footer = e(__('newsletter.email_reason'))
            .'<br><a href="'.e($this->unsubscribeUrl).'" style="color:#9ca3af; text-decoration:underline;">'
            .e(__('newsletter.unsubscribe')).'</a>';

        return $this->subject($this->subjectLine)
            ->view('emails.branded')
            ->with([
                'subject' => $this->subjectLine,
                'preheader' => Str::limit(trim(strip_tags($this->bodyHtml)), 100),
                'heading' => $this->heading,
                'bodyHtml' => $this->bodyHtml,
                'buttonUrl' => $this->ctaUrl,
                'buttonLabel' => $this->ctaLabel,
                'footer' => $footer,
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrl.'>');
            });
    }
}
