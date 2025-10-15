<?php

return [
    'mail' => [
        // Enable sending notification emails for critical transaction updates
        'enabled' => env('NOTIFICATIONS_MAIL_ENABLED', false),

        // Statuses that should trigger email notifications when enabled
        'critical_statuses' => ['failed', 'refunded'],
    ],
];