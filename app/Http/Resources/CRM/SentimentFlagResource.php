<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-004 — API representation for inbound sentiment snapshots
final class SentimentFlagResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'channel' => $this->channel,
            'sentiment_label' => $this->sentiment_label?->value,
            'sentiment_label_text' => $this->sentiment_label?->label(),
            'sentiment_score' => $this->sentiment_score,
            'is_urgent' => $this->is_urgent,
            'rationale' => $this->rationale,
            'source_excerpt' => $this->source_excerpt,
            'indicators' => $this->indicators,
            'model_version' => $this->model_version,
            'flagged_at' => $this->flagged_at?->toIso8601String(),
        ];
    }
}
