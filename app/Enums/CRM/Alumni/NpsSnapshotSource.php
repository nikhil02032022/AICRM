<?php

declare(strict_types=1);

namespace App\Enums\CRM\Alumni;

// BRD: CRM-AL-004 — How the NPS snapshot was ingested: manual admin entry or automated webhook
enum NpsSnapshotSource: string
{
    case Manual  = 'manual';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Manual  => 'Manual Entry',
            self::Webhook => 'Webhook (A2A Alumni)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Manual  => 'badge-slate',
            self::Webhook => 'badge-indigo',
        };
    }
}
