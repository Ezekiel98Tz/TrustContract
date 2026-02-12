<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class DisputeCreatedNotification extends Notification implements ShouldQueue
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
            'type' => 'dispute_created',
            'dispute_id' => $this->dispute->id,
            'contract_id' => $this->dispute->contract_id,
            'initiator_id' => $this->dispute->initiator_id,
            'status' => $this->dispute->status,
            'reason' => $this->dispute->reason,
            'message' => 'A dispute has been opened on your contract.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dispute Opened')
            ->greeting('Hello,')
            ->line('A dispute has been opened on one of your contracts.')
            ->line('Reason: ' . ($this->dispute->reason ?? 'N/A'))
            ->line('Please review the dispute in the dashboard.');
    }
}
