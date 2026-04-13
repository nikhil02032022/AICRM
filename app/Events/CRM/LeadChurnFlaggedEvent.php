<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\ChurnFlag;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-LQ-010 — Fired whenever a new churn risk snapshot is generated for a lead
final class LeadChurnFlaggedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ChurnFlag $churnFlag,
    ) {}
}
