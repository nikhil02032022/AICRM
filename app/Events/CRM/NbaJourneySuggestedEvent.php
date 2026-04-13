<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\NbaJourney;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-010 — Fired when a nurture journey suggestion snapshot is generated
final class NbaJourneySuggestedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly NbaJourney $journey,
    ) {}
}
