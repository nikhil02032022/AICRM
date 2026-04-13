<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-010 — API representation for AI nurture journey suggestions
final class NbaJourneyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'segment_key' => $this->segment_key,
            'segment_label' => $this->segment_label,
            'confidence_score' => $this->confidence_score,
            'rationale' => $this->rationale,
            'steps' => $this->steps,
            'metadata' => $this->metadata,
            'model_version' => $this->model_version,
            'generated_for_date' => $this->generated_for_date?->toDateString(),
            'suggested_at' => $this->suggested_at?->toIso8601String(),
            'applied_at' => $this->applied_at?->toIso8601String(),
        ];
    }
}
