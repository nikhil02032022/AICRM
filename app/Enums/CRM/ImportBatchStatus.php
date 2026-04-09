<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-012 — Import batch lifecycle for bulk CSV/Excel uploads
// BRD: CRM-LC-008 — Also tracks portal webhook batch import status
enum ImportBatchStatus: string
{
    case PENDING    = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED  = 'completed';
    case FAILED     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING    => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED  => 'Completed',
            self::FAILED     => 'Failed',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::PENDING    => 'bg-yellow-100 text-yellow-800',
            self::PROCESSING => 'bg-blue-100 text-blue-800',
            self::COMPLETED  => 'bg-green-100 text-green-800',
            self::FAILED     => 'bg-red-100 text-red-800',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED], true);
    }
}
