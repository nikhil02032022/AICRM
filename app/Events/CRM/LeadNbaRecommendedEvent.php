<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\LeadNbaRecommendation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-002 — Fired when a next best action recommendation is generated for a lead
final class LeadNbaRecommendedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LeadNbaRecommendation $recommendation,
    ) {}
}
