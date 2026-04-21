<?php

declare(strict_types=1);

namespace App\Enums\CRM\Tasks;

// BRD: CRM-TF-002 — Track whether task was created manually or by auto-rule engine
enum TaskSource: string
{
    case Manual = 'manual';
    case Auto   = 'auto';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Auto   => 'Auto',
        };
    }
}
