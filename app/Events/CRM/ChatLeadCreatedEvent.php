<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\ChatLead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LC-006 — Emitted when a chat submission creates a CRM lead
final class ChatLeadCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatLead $chatLead,
    ) {}
}
