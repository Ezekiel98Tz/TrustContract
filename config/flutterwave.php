<?php

return [
    // API base URL
    'base_url' => env('FLW_BASE_URL', 'https://api.flutterwave.com'),

    // API keys
    'public_key' => env('FLW_PUBLIC_KEY', ''),
    'secret_key' => env('FLW_SECRET_KEY', ''),

    // Secret hash for webhook verification (Dashboard -> Settings -> Webhooks)
    'secret_hash' => env('FLW_SECRET_HASH', ''),

    // Enable sandbox/test mode. When true, we will tag payloads and avoid side effects.
    'test_mode' => env('FLW_TEST_MODE', true),

    // Primary currency is TZS, but we support multiple currencies
    'primary_currency' => env('PRIMARY_CURRENCY', 'TZS'),

    // Platform commission rate (e.g., 0.05 for 5%)
    'commission_rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.05),
];