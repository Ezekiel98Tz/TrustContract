<?php

return [
    // Enable or disable webhook simulation endpoints
    'simulation_enabled' => env('WEBHOOKS_SIMULATION_ENABLED', true),

    // Shared secret required in header `X-TC-Webhook-Secret` for simulation calls
    'shared_secret' => env('WEBHOOKS_SHARED_SECRET', 'devsecret'),

    // Provider-specific event -> transaction status mapping
    'status_map' => [
        'stripe' => [
            'payment_intent.succeeded' => 'paid',
            'charge.refunded' => 'refunded',
            'payment_intent.payment_failed' => 'failed',
            'charge.failed' => 'failed',
        ],
        'paypal' => [
            'PAYMENT.SALE.COMPLETED' => 'paid',
            'PAYMENT.SALE.REFUNDED' => 'refunded',
            'PAYMENT.SALE.DENIED' => 'failed',
        ],
        // Simple sandbox provider for quick local tests
        'sandbox' => [
            'transaction.paid' => 'paid',
            'transaction.refunded' => 'refunded',
            'transaction.failed' => 'failed',
        ],
        // Flutterwave simulation mapping (for local tests only)
        'flutterwave' => [
            'charge.completed.successful' => 'paid',
            'charge.completed.failed' => 'failed',
            'refund.processed' => 'refunded',
            // Dispute events are recorded but mapped to failed due to enum
            'charge.dispute.created' => 'failed',
        ],
    ],
];