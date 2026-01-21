<?php

return [
    'mail' => [
        'enabled' => env('NOTIFICATIONS_MAIL_ENABLED', false),
        'critical_statuses' => ['failed', 'refunded'],
    ],
];
