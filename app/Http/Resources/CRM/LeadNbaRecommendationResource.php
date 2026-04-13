<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-002 — API representation for next best action recommendation snapshots
final class LeadNbaRecommendationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'recommended_action' => $this->recommended_action,
            'reasoning' => $this->reasoning,
            'confidence_score' => $this->confidence_score,
            'channels' => $this->channels,
            'metadata' => $this->metadata,
            'model_version' => $this->model_version,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
        ];
    }
}
