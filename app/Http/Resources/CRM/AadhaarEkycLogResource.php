<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-DM-007 — API resource for Aadhaar eKYC log (mobile/ERP consumers)
final class AadhaarEkycLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'lead_uuid'        => $this->lead?->uuid,
            'status'           => $this->status?->value,
            'status_label'     => $this->status?->label(),
            'kyc_complete'     => $this->kyc_complete,
            'name_match'       => $this->name_match,
            'kyc_completed_at' => $this->kyc_completed_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            // BRD: CRM-DM-007 — No Aadhaar number or OTP reference exposed in API response (DPDP)
        ];
    }
}
