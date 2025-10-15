<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contract_rejected',
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'status' => $this->contract->status,
            'message' => 'The contract was rejected by the other party.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contract Rejected')
            ->greeting('Hello,')
            ->line('Your contract was rejected: ' . $this->contract->title);
    }
}