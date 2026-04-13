<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-TC-002 — Script lifecycle status
enum CallScriptStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}
