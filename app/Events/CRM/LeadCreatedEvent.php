<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-011 — Fired after a new lead is persisted to the database
final class LeadCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
    ) {}
}
