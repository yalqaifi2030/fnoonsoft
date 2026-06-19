<?php

namespace App\Notifications;

use App\Support\MailTemplate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordBranded extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return MailTemplate::mailMessage('reset', [
            'name' => method_exists($notifiable, 'displayName') ? $notifiable->displayName() : ($notifiable->name ?? ''),
            'minutes' => $minutes,
        ], $this->resetUrl($notifiable));
    }
}
