<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-EI-008 — API resource for alumni bridge log (mobile/ERP consumers)
final class AlumniBridgeLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'lead_uuid'       => $this->lead?->uuid,
            'erp_student_id'  => $this->erp_student_id,
            'erp_alumni_id'   => $this->erp_alumni_id,
            'status'          => $this->status?->value,
            'status_label'    => $this->status?->label(),
            'referral_code'   => $this->referral_code,
            'referrals_count' => $this->referrals_count,
            'bridged_at'      => $this->bridged_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
