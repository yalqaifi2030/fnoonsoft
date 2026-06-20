<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Fan out one newsletter to every active subscriber. Each message is queued
 * individually (the server runs a queue worker) and carries the subscriber's
 * own unsubscribe link, in their saved locale.
 */
class SendNewsletter implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public string $bodyHtml,
        public ?string $ctaUrl = null,
        public ?string $ctaLabel = null,
    ) {}

    public function handle(): void
    {
        NewsletterSubscriber::query()
            ->active()
            ->whereNotNull('email')
            ->chunkById(200, function ($subscribers) {
                foreach ($subscribers as $sub) {
                    try {
                        Mail::to($sub->email)
                            ->locale($sub->locale ?: config('app.locale'))
                            ->queue(new NewsletterMail(
                                $this->subjectLine,
                                $this->heading,
                                $this->bodyHtml,
                                $this->ctaUrl,
                                $this->ctaLabel,
                                $sub->unsubscribeUrl(),
                            ));
                    } catch (\Throwable $e) {
                        // a single bad address must never abort the whole send
                    }
                }
            });
    }
}
