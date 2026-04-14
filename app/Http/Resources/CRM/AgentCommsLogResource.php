<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AG-008 — API resource for agent bulk comms log (mobile/ERP consumers)
final class AgentCommsLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'channel'          => $this->channel?->value,
            'channel_label'    => $this->channel?->label(),
            'recipient_count'  => $this->recipient_count,
            'delivered_count'  => $this->delivered_count,
            'failed_count'     => $this->failed_count,
            'status'           => $this->status,
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            // BRD: CRM-AG-008 — recipient_agent_ids not exposed in API response (DPDP)
        ];
    }
}
