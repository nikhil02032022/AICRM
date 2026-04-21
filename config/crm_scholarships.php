<?php

declare(strict_types=1);

// BRD: CRM-FM-006 to CRM-FM-009 — Scholarships, waivers, installments, approval chain
return [

    'approval_chain' => [
        // Ordered stage => role slug (matches Spatie role names seeded in CrmScholarshipRolePermissionSeeder)
        'counsellor' => env('CRM_SCHOLARSHIP_ROLE_COUNSELLOR', 'counsellor'),
        'manager'    => env('CRM_SCHOLARSHIP_ROLE_MANAGER', 'manager'),
        'finance'    => env('CRM_SCHOLARSHIP_ROLE_FINANCE', 'finance'),
    ],

    'max_awards_per_application' => (int) env('CRM_SCHOLARSHIP_MAX_AWARDS', 3),

    'escalation' => [
        'sla_hours' => (int) env('CRM_SCHOLARSHIP_SLA_HOURS', 48),
        'cron'      => env('CRM_SCHOLARSHIP_ESCALATION_CRON', '*/15 * * * *'),
    ],

    'impact' => [
        // FM-012 — cached aggregate for dashboard tile
        'cache_ttl' => (int) env('CRM_SCHOLARSHIP_IMPACT_TTL', 300),
    ],

    // Whitelisted attributes the eligibility evaluator may read.
    // Extend this list as domain columns are added (e.g. entrance_score on applications).
    'eligibility_attributes' => [
        'application.programme_id',
        'lead.source',
        'lead.lead_score',
        'lead.marks_10th',
        'lead.marks_12th',
        'lead.graduation_percentage',
    ],
];
