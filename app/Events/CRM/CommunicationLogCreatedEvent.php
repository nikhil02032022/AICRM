<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\CommunicationLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-001 — Fired when a new communication log entry is created; used to trigger prediction refresh
final class CommunicationLogCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CommunicationLog $log,
    ) {}
}
