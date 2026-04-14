<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-AR-020 — Delivery job status for report_deliveries
enum ReportDeliveryStatus: string
{
    case QUEUED  = 'queued';
    case SENDING = 'sending';
    case SENT    = 'sent';
    case FAILED  = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED  => 'Queued',
            self::SENDING => 'Sending',
            self::SENT    => 'Sent',
            self::FAILED  => 'Failed',
        };
    }
}
