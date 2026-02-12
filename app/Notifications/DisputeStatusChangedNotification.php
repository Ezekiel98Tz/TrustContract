<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class DisputeStatusChangedNotification extends Notification implements ShouldQueue
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
            'type' => 'dispute_status_changed',
            'dispute_id' => $this->dispute->id,
            'contract_id' => $this->dispute->contract_id,
            'status' => $this->dispute->status,
            'resolution' => $this->dispute->resolution,
            'resolved_at' => $this->dispute->resolved_at,
            'message' => 'Dispute status updated: ' . $this->dispute->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Dispute Status Updated';
        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello,')
            ->line('The status of a dispute has been updated to: ' . $this->dispute->status)
            ->when($this->dispute->resolution, fn($m) => $m->line('Outcome: ' . $this->dispute->resolution))
            ->line('Please review details in the dashboard.');
    }
}
