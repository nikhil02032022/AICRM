<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AiSuggestionDecision;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-011 — Fired when user accepts, edits, or dismisses an AI suggestion
final class AiSuggestionDecisionRecordedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AiSuggestionDecision $decision,
    ) {}
}
