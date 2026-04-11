<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // --- CRM Communication Engine ---

    'whatsapp' => [
        'default_bsp'  => env('WHATSAPP_DEFAULT_BSP', 'meta'),

        'meta' => [
            'phone_number_id' => env('META_WA_PHONE_NUMBER_ID', ''),
            'access_token'    => env('META_WA_ACCESS_TOKEN', ''),
            'app_secret'      => env('META_WA_APP_SECRET', ''),
        ],

        'interakt' => [
            'api_key'        => env('INTERAKT_API_KEY', ''),
            'webhook_secret' => env('INTERAKT_WEBHOOK_SECRET', ''),
        ],

        'gupshup' => [
            'api_key'        => env('GUPSHUP_API_KEY', ''),
            'app_name'       => env('GUPSHUP_APP_NAME', ''),
            'source_phone'   => env('GUPSHUP_SOURCE_PHONE', ''),
            'webhook_secret' => env('GUPSHUP_WEBHOOK_SECRET', ''),
        ],
    ],

    'sms' => [
        'default_gateway' => env('SMS_DEFAULT_GATEWAY', 'msg91'),

        'msg91' => [
            'auth_key'   => env('MSG91_AUTH_KEY', ''),
            'sender_id'  => env('MSG91_SENDER_ID', ''),
            'route'      => env('MSG91_ROUTE', '4'),
        ],

        'textlocal' => [
            'api_key'   => env('TEXTLOCAL_API_KEY', ''),
            'sender'    => env('TEXTLOCAL_SENDER', ''),
        ],

        'kaleyra' => [
            'api_key'   => env('KALEYRA_API_KEY', ''),
            'sid'       => env('KALEYRA_SID', ''),
            'sender_id' => env('KALEYRA_SENDER_ID', ''),
        ],
    ],

    // BRD: CRM-LC-020 — A2A ERP Student Master outbound lookup (fallback for demo/single-tenant mode)
    // Per-institution credentials are stored in integration_credentials (channel = erp_a2a).
    'a2a_erp' => [
        'base_url' => env('A2A_ERP_BASE_URL', ''),
        'timeout'  => (int) env('A2A_ERP_TIMEOUT', 10),
    ],

    'telephony' => [
        'default_provider' => env('TELEPHONY_DEFAULT_PROVIDER', 'exotel'),

        'exotel' => [
            'api_key'    => env('EXOTEL_API_KEY', ''),
            'api_token'  => env('EXOTEL_API_TOKEN', ''),
            'sid'        => env('EXOTEL_SID', ''),
            'caller_id'  => env('EXOTEL_CALLER_ID', ''),
        ],

        'ozonetel' => [
            'api_key'    => env('OZONETEL_API_KEY', ''),
            'username'   => env('OZONETEL_USERNAME', ''),
        ],

        'knowlarity' => [
            'api_key'       => env('KNOWLARITY_API_KEY', ''),
            'auth_token'    => env('KNOWLARITY_AUTH_TOKEN', ''),
            'caller_id'     => env('KNOWLARITY_CALLER_ID', ''),
        ],
    ],

];
