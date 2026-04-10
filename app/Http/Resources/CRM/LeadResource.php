<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BRD: CRM-LC-011 — API representation of a Lead record.
 * Never expose auto-increment `id` — use `uuid` only.
 *
 * @mixin Lead
 */
final class LeadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            // BRD: CRM-CR-002 — mobile/email are only included for authorised roles with PII permission
            'mobile' => $this->when(
                $request->user()?->can('crm.leads.view_pii'),
                fn () => $this->mobile,
            ),
            'email' => $this->when(
                $request->user()?->can('crm.leads.view_pii'),
                fn () => $this->email,
            ),
            'source' => $this->source?->value,
            'source_label' => $this->source?->label(),
            'lead_score' => $this->lead_score,
            'temperature' => $this->temperature?->value,
            'temperature_label' => $this->temperature?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'city' => $this->city,
            'state' => $this->state,
            'consent_given' => $this->consent_given,
            'opt_out' => $this->opt_out,
            'is_anonymised' => $this->isAnonymised(),
            'assigned_counsellor' => $this->whenLoaded('assignedCounsellor', fn () => [
                'id' => $this->assignedCounsellor->id,
                'name' => $this->assignedCounsellor->name,
            ]),
            'programme_interests' => $this->whenLoaded('programmeInterests', fn () => $this->programmeInterests->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'is_primary' => (bool) $p->pivot->is_primary,
            ])
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
