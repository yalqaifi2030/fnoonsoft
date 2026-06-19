<?php

namespace App\Notifications;

use App\Support\MailTemplate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailBranded extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return MailTemplate::mailMessage('verify', [
            'name' => method_exists($notifiable, 'displayName') ? $notifiable->displayName() : ($notifiable->name ?? ''),
        ], $this->verificationUrl($notifiable));
    }
}
