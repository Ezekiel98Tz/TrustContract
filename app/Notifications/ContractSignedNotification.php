<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract, public string $byRole)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contract_signed',
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'status' => $this->contract->status,
            'by_role' => $this->byRole,
            'message' => 'A party has signed the contract.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Contract Signed')
            ->greeting('Hello,')
            ->line('A party has signed the contract: ' . $this->contract->title)
            ->line('Current status: ' . $this->contract->status);
    }
}