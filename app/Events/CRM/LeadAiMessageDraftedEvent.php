<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AiMessageDraft;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-003 — Fired when AI communication draft is generated for a lead
final class LeadAiMessageDraftedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AiMessageDraft $draft,
    ) {}
}
