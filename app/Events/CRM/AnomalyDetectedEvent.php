<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AnomalyAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-009 — Fired when anomaly detection creates a new drop-off alert
final class AnomalyDetectedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AnomalyAlert $anomalyAlert,
    ) {}
}
