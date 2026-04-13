<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-009 — API representation for anomaly alert snapshots
final class AnomalyAlertResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'alert_type' => $this->alert_type,
            'metric_name' => $this->metric_name,
            'current_value' => $this->current_value,
            'baseline_value' => $this->baseline_value,
            'deviation_percent' => $this->deviation_percent,
            'threshold_percent' => $this->threshold_percent,
            'severity' => $this->severity,
            'rationale' => $this->rationale,
            'metadata' => $this->metadata,
            'model_version' => $this->model_version,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
