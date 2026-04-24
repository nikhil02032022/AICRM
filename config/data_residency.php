<?php

// BRD: CRM-CR-006 — Data residency configuration
return [
    'storage_region' => env('STORAGE_REGION', 'ap-south-1'),

    'enforce_in_environments' => ['production', 'staging'],

    'allowed_disks' => ['s3', 'local'],
];
