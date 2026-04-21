<?php

declare(strict_types=1);

namespace App\Enums\CRM\Documents;

// BRD: CRM-DM-004 — Reviewer comments (internal vs applicant-visible)
enum DocumentCommentType: string
{
    case INTERNAL = 'internal';
    case APPLICANT_VISIBLE = 'applicant_visible';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
