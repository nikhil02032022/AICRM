<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Enums\CRM\LeadStatus;
use App\Models\CRM\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-001 — Fired on every lead status transition for audit, scoring, and automation triggers
final class LeadStatusChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadStatus $previousStatus,
        public readonly LeadStatus $newStatus,
    ) {}
}
