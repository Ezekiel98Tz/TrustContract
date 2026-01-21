<?php

namespace App\Notifications;

use App\Models\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoginNotification extends Notification
{
    use Queueable;

    public function __construct(private UserDevice $device)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Login Detected')
            ->line('A new login was detected on your account.')
            ->line('IP: ' . ($this->device->ip_address ?? 'Unknown'))
            ->line('Device: ' . (substr($this->device->user_agent ?? 'Unknown', 0, 120)))
            ->line('Time: ' . ($this->device->first_seen_at?->toDateTimeString() ?? now()->toDateTimeString()))
            ->line('If this wasn’t you, revoke the device immediately from Account → Devices and change your password.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security',
            'message' => 'New login detected',
            'device_id' => $this->device->id,
        ];
    }
}
