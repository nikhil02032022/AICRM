<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-001 — API representation for dialler queue entries
final class DiallerLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead?->uuid,
            'call_log_uuid' => $this->callLog?->uuid,
            'queue_order' => $this->queue_order,
            'status' => $this->status?->value,
            'failure_reason' => $this->failure_reason,
            'attempted_at' => $this->attempted_at?->toIso8601String(),
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }
}
