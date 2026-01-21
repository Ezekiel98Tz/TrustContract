<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(private string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Two-Factor Code')
            ->line('Use the following 2FA code to complete your login:')
            ->line("Code: {$this->code}")
            ->line('This code expires in 10 minutes.')
            ->line('If you did not request this, please secure your account.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'two_factor',
            'message' => 'Two-factor code sent',
        ];
    }
}
