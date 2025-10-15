<?php

namespace App\Notifications;

use App\Models\Verification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VerificationReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(public Verification $verification)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'verification_reviewed',
            'verification_id' => $this->verification->id,
            'status' => $this->verification->status,
            'notes' => $this->verification->notes,
            'reviewed_by' => $this->verification->reviewed_by,
            'reviewed_at' => $this->verification->reviewed_at,
            'message' => $this->verification->status === 'approved'
                ? 'Your verification has been approved.'
                : 'Your verification has been rejected.',
        ];
    }
}