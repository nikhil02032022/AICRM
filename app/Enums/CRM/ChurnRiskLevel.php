<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LQ-010 — Risk tiers used by predictive churn flagging
enum ChurnRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low Risk',
            self::MEDIUM => 'Medium Risk',
            self::HIGH => 'High Risk',
        };
    }
}
