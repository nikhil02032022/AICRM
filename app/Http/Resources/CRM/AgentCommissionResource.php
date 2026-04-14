<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AG-006 — API resource for agent commission record (mobile/ERP consumers)
final class AgentCommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'lead_uuid'         => $this->lead?->uuid,
            'agent_user_id'     => $this->agent_user_id,
            'commission_type'   => $this->commission_type,
            'commission_amount' => $this->commission_amount,
            'currency'          => $this->currency,
            'status'            => $this->status?->value,
            'status_label'      => $this->status?->label(),
            'approved_at'       => $this->approved_at?->toIso8601String(),
            'paid_at'           => $this->paid_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
