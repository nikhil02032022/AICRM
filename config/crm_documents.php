<?php

declare(strict_types=1);

// BRD: CRM-DM-001 to CRM-DM-010 — Document management (core)
return [

    'storage' => [
        'disk'         => env('CRM_DOCUMENTS_DISK', 'encrypted_documents'),
        'root'         => storage_path('app/private/crm_documents'),
        'max_size_kb'  => (int) env('CRM_DOCUMENTS_MAX_SIZE_KB', 10240),
    ],

    'reminders' => [
        // DM-005 — cadence relative to created_at of pending document (days).
        'cadence_days'  => [1, 3, 7],
        'dispatch_cron' => '*/15 * * * *',
    ],

    'bulk_download' => [
        // DM-009
        'zip_disk'     => env('CRM_DOCUMENTS_ZIP_DISK', 'local'),
        'zip_root'     => storage_path('app/private/crm_document_zips'),
        'ttl_minutes'  => (int) env('CRM_DOCUMENTS_BULK_TTL_MINUTES', 60),
        'max_files'    => (int) env('CRM_DOCUMENTS_BULK_MAX_FILES', 2000),
    ],

    'allowed_mime_defaults' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    'completeness' => [
        // DM-010 — weight given to mandatory vs optional items.
        'mandatory_weight' => 1.0,
        'optional_weight'  => 0.25,
        'cache_ttl'        => 120,
    ],
];
