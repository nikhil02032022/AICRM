<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AlumniBridgeLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EI-008 — Fired when an alumni bridge handoff is triggered for a graduated student
final class AlumniBridgeTriggeredEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AlumniBridgeLog $bridgeLog
    ) {}
}
