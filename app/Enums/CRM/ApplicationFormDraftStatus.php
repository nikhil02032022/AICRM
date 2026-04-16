<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AP-003 — Draft lifecycle states for save-and-resume
enum ApplicationFormDraftStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case EXPIRED = 'expired';
}
