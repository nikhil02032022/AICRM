<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\OfferLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BRD: CRM-AP-012, CRM-AP-013 — API representation of an OfferLetter record.
 *
 * @mixin OfferLetter
 */
final class OfferLetterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'application_uuid' => $this->application_uuid,
            'lead_uuid' => $this->lead_uuid,
            'programme_uuid' => $this->programme_uuid,
            'status' => $this->status,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sent_via' => $this->sent_via,
            'acceptance_recorded_at' => $this->acceptance_recorded_at?->toIso8601String(),
            'declined_at' => $this->declined_at?->toIso8601String(),
            'decline_reason' => $this->decline_reason,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_accepted' => $this->isAccepted(),
            'is_declined' => $this->isDeclined(),
            'is_expired' => $this->isExpired(),
            'is_valid_for_acceptance' => $this->isValidForAcceptance(),
            'created_at' => $this->created_at?->toIso8601String(),
            'delivery_status' => $this->delivery_status,
            'delivery_message_id' => $this->delivery_message_id,
        ];
    }
}
