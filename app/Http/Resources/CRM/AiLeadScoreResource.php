<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-LQ-003 — API representation of AI-assisted lead score snapshots
final class AiLeadScoreResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'score' => $this->score,
            'explanation' => $this->explanation,
            'model_version' => $this->model_version,
            'metadata' => $this->metadata,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
