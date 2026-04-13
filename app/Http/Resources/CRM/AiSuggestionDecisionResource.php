<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AI-011 — API representation for AI suggestion decision actions
final class AiSuggestionDecisionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'suggestion_type' => $this->suggestion_type,
            'suggestion_uuid' => $this->suggestion_uuid,
            'decision' => $this->decision,
            'edited_content' => $this->edited_content,
            'notes' => $this->notes,
            'acted_by' => $this->actor?->name,
            'acted_at' => $this->acted_at?->toIso8601String(),
        ];
    }
}
