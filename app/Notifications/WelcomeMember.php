<?php

namespace App\Notifications;

use App\Support\MailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMember extends Notification
{
    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return MailTemplate::mailMessage('welcome', [
            'name' => method_exists($notifiable, 'displayName') ? $notifiable->displayName() : ($notifiable->name ?? ''),
        ], url('/dashboard'));
    }
}
