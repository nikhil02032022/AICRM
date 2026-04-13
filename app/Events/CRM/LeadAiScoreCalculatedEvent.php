<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AiLeadScore;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LQ-003 — Fired when an AI-assisted score snapshot is calculated and persisted
final class LeadAiScoreCalculatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AiLeadScore $aiLeadScore,
    ) {}
}
