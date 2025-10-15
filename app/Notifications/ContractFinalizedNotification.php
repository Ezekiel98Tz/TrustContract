<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractFinalizedNotification extends Notification
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
            'type' => 'contract_finalized',
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'status' => $this->contract->status,
            'message' => 'The contract has been finalized by admin.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contract Finalized')
            ->greeting('Hello,')
            ->line('The contract has been finalized: ' . $this->contract->title)
            ->line('You can now download or archive the agreement.');
    }
}