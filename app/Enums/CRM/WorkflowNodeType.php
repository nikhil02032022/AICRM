<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-MA-001 — Node categories for multi-step automation workflows
enum WorkflowNodeType: string
{
    case TRIGGER = 'trigger';
    case CONDITION = 'condition';
    case ACTION = 'action';

    public function label(): string
    {
        return match ($this) {
            self::TRIGGER => 'Trigger',
            self::CONDITION => 'Condition',
            self::ACTION => 'Action',
        };
    }
}
