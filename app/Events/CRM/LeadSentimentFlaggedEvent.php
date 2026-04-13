<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\SentimentFlag;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-004 — Fired when inbound sentiment analysis produces a sentiment snapshot
final class LeadSentimentFlaggedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SentimentFlag $sentimentFlag,
    ) {}
}
