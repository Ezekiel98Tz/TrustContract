<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommissionRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'commission_recorded',
            'transaction_id' => $this->transaction->id,
            'contract_id' => $this->transaction->contract_id,
            'currency' => $this->transaction->currency,
            'seller_id' => $this->transaction->payee_id,
            'reference' => $this->transaction->reference,
            'message' => 'Platform commission recorded for a paid transaction.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $txn = $this->transaction;
        $amount = number_format(($txn->amount_cents ?? 0) / 100, 2);

        return (new MailMessage)
            ->subject('Commission Recorded')
            ->greeting('Hello,')
            ->line('A platform commission has been recorded for a paid transaction.')
            ->line('Transaction ID: ' . $txn->id)
            ->line('Contract ID: ' . $txn->contract_id)
            ->line("Amount: {$amount} {$txn->currency}")
            ->line('Reference: ' . ($txn->reference ?? 'N/A'));
    }
}