<?php

declare(strict_types=1);

// BRD: CRM-FM-001 to CRM-FM-013 — Fee, Scholarship and Payment Management config
return [

    'default_currency' => env('CRM_PAYMENTS_CURRENCY', 'INR'),

    'default_gateway' => env('CRM_PAYMENTS_DEFAULT_GATEWAY', 'razorpay'),

    'link' => [
        'ttl_minutes' => (int) env('CRM_PAYMENTS_LINK_TTL_MINUTES', 60 * 24 * 3),
        'route_name'  => 'crm.payments.pay',
        // Note: full route name resolves to "crm.payments.pay" within the crm.* group.
    ],

    'reminders' => [
        // Offsets (in days) relative to due date. Negative = before, positive = after.
        'cadence_days' => [-3, -1, 1],
        'dispatch_cron' => '*/15 * * * *',
    ],

    'webhook' => [
        'tolerance_seconds' => 300,
    ],

    'gateways' => [

        'razorpay' => [
            'driver'      => 'razorpay',
            'key_id'      => env('RAZORPAY_KEY_ID'),
            'key_secret'  => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'base_url'    => env('RAZORPAY_BASE_URL', 'https://api.razorpay.com/v1'),
        ],

        'payu' => [
            'driver'      => 'payu',
            'merchant_key' => env('PAYU_MERCHANT_KEY'),
            'merchant_salt' => env('PAYU_MERCHANT_SALT'),
            'webhook_secret' => env('PAYU_WEBHOOK_SECRET'),
            'base_url'    => env('PAYU_BASE_URL', 'https://test.payu.in'),
        ],

        'ccavenue' => [
            'driver'      => 'ccavenue',
            'merchant_id' => env('CCAVENUE_MERCHANT_ID'),
            'access_code' => env('CCAVENUE_ACCESS_CODE'),
            'working_key' => env('CCAVENUE_WORKING_KEY'),
            'base_url'    => env('CCAVENUE_BASE_URL', 'https://test.ccavenue.com'),
        ],
    ],

    // Keys to redact before persisting raw_request / raw_response payloads.
    'redact_keys' => [
        'card_number', 'cvv', 'card_cvv', 'card_expiry',
        'upi_vpa', 'vpa', 'account_number', 'ifsc',
        'password', 'authorization',
    ],
];
