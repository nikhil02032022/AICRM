<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-005 — API representation for supervisor monitoring session logs
final class CallMonitorLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'mode' => $this->mode?->value,
            'status' => $this->status?->value,
            'provider_session_id' => $this->provider_session_id,
            'consent_validated' => $this->consent_validated,
            'duration_seconds' => $this->duration_seconds,
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'call_log' => $this->whenLoaded('callLog', function (): array {
                return [
                    'uuid' => $this->callLog?->uuid,
                    'status' => $this->callLog?->status?->value,
                    'lead_name' => $this->callLog?->lead?->name,
                ];
            }),
            'supervisor' => $this->whenLoaded('supervisor', function (): array {
                return [
                    'id' => $this->supervisor?->id,
                    'name' => $this->supervisor?->name,
                ];
            }),
        ];
    }
}
