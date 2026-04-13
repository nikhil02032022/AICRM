<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-LQ-010 — API representation of lead churn risk snapshots
final class ChurnFlagResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'risk_level' => $this->risk_level?->value,
            'risk_label' => $this->risk_level?->label(),
            'risk_score' => $this->risk_score,
            'rationale' => $this->rationale,
            'indicators' => $this->indicators,
            'flagged_at' => $this->flagged_at?->toIso8601String(),
            'mitigated_at' => $this->mitigated_at?->toIso8601String(),
        ];
    }
}
