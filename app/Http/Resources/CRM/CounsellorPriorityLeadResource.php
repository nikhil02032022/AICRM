<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-005 — API representation for counsellor daily priority lead snapshots
final class CounsellorPriorityLeadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'lead_name' => $this->lead?->fullName(),
            'priority_rank' => $this->priority_rank,
            'priority_score' => $this->priority_score,
            'reasoning' => $this->reasoning,
            'factors' => $this->factors,
            'generated_for_date' => $this->generated_for_date?->toDateString(),
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
