<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-006 — API representation for telecalling campaign management
final class TelecallingCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value,
            'start_time_window' => $this->start_time_window?->toIso8601String(),
            'end_time_window' => $this->end_time_window?->toIso8601String(),
            'launched_at' => $this->launched_at?->toIso8601String(),
            'agents_count' => (int) ($this->agents_count ?? $this->agents()->count()),
            'leads_count' => (int) ($this->leads_count ?? $this->leads()->count()),
            'dialler_sessions_count' => (int) ($this->dialler_sessions_count ?? $this->diallerSessions()->count()),
        ];
    }
}
