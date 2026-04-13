<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-003 — API representation of AI communication draft snapshots
final class AiMessageDraftResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'draft_text' => $this->draft_text,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'model_version' => $this->model_version,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
        ];
    }
}
