<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BRD: CRM-AP-008, CRM-AP-009 — API representation of an Application record.
 * Never expose auto-increment `id` — use `uuid` only.
 *
 * @mixin Application
 */
final class ApplicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'lead_uuid' => $this->lead_uuid,
            'lead_name' => $this->lead?->first_name . ' ' . $this->lead?->last_name,
            'lead_email' => $this->lead?->email,
            'application_form_draft_uuid' => $this->application_form_draft_uuid,
            'admission_cycle_uuid' => $this->admission_cycle_uuid,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_badge_colour' => $this->status?->badgeColour(),
            'stage_entered_at' => $this->stage_entered_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'assigned_counsellor_id' => $this->assigned_counsellor_id,
            'assigned_counsellor_name' => $this->assignedCounsellor?->name,
            'current_offer' => $this->whenLoaded('currentOfferLetter', function () {
                return new OfferLetterResource($this->currentOfferLetter);
            }),
            'is_ready_for_conversion' => $this->currentOfferLetter?->isAccepted() ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
