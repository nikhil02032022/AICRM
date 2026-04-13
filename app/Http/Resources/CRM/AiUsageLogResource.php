<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-012 — API representation for AI usage logs used in compliance audits
final class AiUsageLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'actor_name' => $this->actor?->name,
            'feature_key' => $this->feature_key,
            'action' => $this->action,
            'event_name' => $this->event_name,
            'reference_uuid' => $this->reference_uuid,
            'context' => $this->context,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
