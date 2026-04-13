<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\ChatLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-006 — Fired when conversational AI classifies a chat for live-agent escalation
final class ChatbotEscalationEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ChatLead $chatLead,
        public readonly string $reason,
    ) {}
}
