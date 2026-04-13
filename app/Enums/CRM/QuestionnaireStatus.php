<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LQ-009 — Lifecycle state of qualification questionnaires
enum QuestionnaireStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::ARCHIVED => 'Archived',
        };
    }
}
