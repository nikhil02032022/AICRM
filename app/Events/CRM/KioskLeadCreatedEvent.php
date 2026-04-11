<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-013 — Emitted when a walk-in enquiry is captured via kiosk flow
final class KioskLeadCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
    ) {}
}