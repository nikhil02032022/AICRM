<?php

declare(strict_types=1);

namespace App\Enums\CRM\Documents;

// BRD: CRM-DM-009 — Bulk document download job lifecycle
enum BulkDownloadStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function isTerminal(): bool
    {
        return in_array($this, [self::READY, self::FAILED, self::EXPIRED], true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
