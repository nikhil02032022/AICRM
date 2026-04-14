<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-DM-006 — API resource for DigiLocker document (mobile/ERP consumers)
final class DigiLockerDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'lead_uuid'              => $this->lead?->uuid,
            'status'                 => $this->status?->value,
            'status_label'           => $this->status?->label(),
            'document_type'          => $this->document_type,
            'digilocker_request_id'  => $this->digilocker_request_id,
            'is_verified'            => $this->is_verified,
            'verified_at'            => $this->verified_at?->toIso8601String(),
            'created_at'             => $this->created_at?->toIso8601String(),
        ];
    }
}
