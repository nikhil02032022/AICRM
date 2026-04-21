<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    */
    'otp_expiry_minutes' => (int) env('PORTAL_OTP_EXPIRY_MINUTES', 10),

    'otp_max_attempts' => (int) env('PORTAL_OTP_MAX_ATTEMPTS', 5),

    'otp_rate_limit_window_minutes' => (int) env('PORTAL_OTP_RATE_LIMIT_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */
    'session_lifetime_hours' => (int) env('PORTAL_SESSION_LIFETIME_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | ERP Bridge
    |--------------------------------------------------------------------------
    */
    'erp_bridge_base_url' => env('ERP_BRIDGE_BASE_URL', ''),

    'erp_bridge_token_expiry_minutes' => (int) env('ERP_BRIDGE_TOKEN_EXPIRY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Branding Defaults
    | Used when an institution cannot be resolved from the request domain.
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'default_logo'             => env('PORTAL_DEFAULT_LOGO', 'images/portal-default-logo.svg'),
        'default_primary_color'    => env('PORTAL_DEFAULT_PRIMARY_COLOR', '#4f46e5'),
        'default_institution_name' => env('PORTAL_DEFAULT_INSTITUTION_NAME', 'Applicant Portal'),
    ],

];
