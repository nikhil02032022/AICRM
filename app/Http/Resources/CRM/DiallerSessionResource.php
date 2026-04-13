<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-001 — API representation for dialler sessions
final class DiallerSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'campaign_name' => $this->campaign_name,
            'status' => $this->status?->value,
            'total_leads' => $this->total_leads,
            'queued_calls' => $this->queued_calls,
            'placed_calls' => $this->placed_calls,
            'skipped_calls' => $this->skipped_calls,
            'failed_calls' => $this->failed_calls,
            'started_at' => $this->started_at?->toIso8601String(),
            'last_dialled_at' => $this->last_dialled_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'starter' => $this->starter?->name,
            'logs' => DiallerLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
