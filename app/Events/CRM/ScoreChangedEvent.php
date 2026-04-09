<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LQ-006 — Fired when a lead's numeric score changes (auto or manual override)
final class ScoreChangedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly int  $oldScore,
        public readonly int  $newScore,
    ) {}
}
