<?php

namespace App\Notifications;

use App\Models\BusinessVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class BusinessVerificationReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessVerification $verification)
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
            'type' => 'business_verification_reviewed',
            'business_verification_id' => $this->verification->id,
            'business_id' => $this->verification->business_id,
            'status' => $this->verification->status,
            'notes' => $this->verification->notes,
            'reviewed_by' => $this->verification->reviewed_by,
            'reviewed_at' => $this->verification->reviewed_at,
            'message' => $this->verification->status === 'approved'
                ? 'Your business verification has been approved.'
                : 'Your business verification has been rejected.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->verification->status === 'approved';
        return (new MailMessage)
            ->subject($approved ? 'Business Verification Approved' : 'Business Verification Rejected')
            ->greeting('Hello,')
            ->line($approved ? 'Your business verification has been approved.' : 'Your business verification has been rejected.')
            ->line('Thanks for using TrustContract.');
    }
}
