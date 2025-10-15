<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TransactionUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $mailEnabled = (bool) config('notifications.mail.enabled', false);
        $criticalStatuses = (array) config('notifications.mail.critical_statuses', ['failed', 'refunded']);
        if ($mailEnabled && in_array($this->transaction->status, $criticalStatuses, true) && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'transaction_updated',
            'transaction_id' => $this->transaction->id,
            'contract_id' => $this->transaction->contract_id,
            'contract_title' => $this->transaction->contract?->title,
            'status' => $this->transaction->status,
            'amount_cents' => $this->transaction->amount_cents,
            'currency' => $this->transaction->currency,
            'reference' => $this->transaction->reference,
            'payer_name' => $this->transaction->payer?->name,
            'payee_name' => $this->transaction->payee?->name,
            'message' => match ($this->transaction->status) {
                'paid' => 'A payment has been recorded.',
                'failed' => 'A transaction has failed.',
                'refunded' => 'A payment has been refunded.',
                default => 'Transaction status updated.',
            },
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $txn = $this->transaction;
        $statusLabel = ucfirst($txn->status);
        $contractTitle = $txn->contract?->title ?? 'N/A';
        $amount = number_format($txn->amount_cents / 100, 2);
        $appUrl = rtrim(config('app.url'), '/');
        $dashboardUrl = $appUrl . '/dashboard';

        return (new MailMessage)
            ->subject("Transaction Update: {$statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line(match ($txn->status) {
                'paid' => 'A payment has been recorded on your contract.',
                'failed' => 'A transaction has failed on your contract.',
                'refunded' => 'A payment has been refunded on your contract.',
                default => 'A transaction status has been updated.',
            })
            ->line("Contract: {$contractTitle}")
            ->line("Amount: {$amount} {$txn->currency}")
            ->line('Reference: ' . ($txn->reference ?? 'N/A'))
            ->action('View Dashboard', $dashboardUrl);
    }
}