<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class MediatorAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Dispute $dispute)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $mailEnabled = (bool) config('notifications.mail.enabled', false);
        if ($mailEnabled && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mediator_assigned',
            'dispute_id' => $this->dispute->id,
            'contract_id' => $this->dispute->contract_id,
            'mediator_id' => $this->dispute->mediator_id,
            'message' => 'You have been assigned as mediator for a dispute.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mediator Assignment')
            ->greeting('Hello,')
            ->line('You have been assigned as mediator for a dispute.')
            ->line('Please review the dispute in the admin dashboard.');
    }
}
