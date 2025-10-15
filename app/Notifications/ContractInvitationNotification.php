<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractInvitationNotification extends Notification
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
            'type' => 'contract_invitation',
            'contract_id' => $this->contract->id,
            'title' => $this->contract->title,
            'price_cents' => $this->contract->price_cents,
            'currency' => $this->contract->currency,
            'message' => 'You have a new contract invitation to review and sign.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Contract Invitation')
            ->greeting('Hello,')
            ->line('You have received a new contract invitation.')
            ->line('Title: ' . $this->contract->title)
            ->line('Please log in to review and sign.');
    }
}