<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\ChatLead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatLead
 */
final class ChatLeadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'session_id' => $this->session_id,
            'handoff_status' => $this->handoff_status,
            'visitor_name' => $this->visitor_name,
            'source_url' => $this->source_url,
            'transcript' => $this->transcript,
            'consent_given' => $this->consent_given,
            'consent_form_version' => $this->consent_form_version,
            'attribution_params' => $this->attribution_params,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'inbound_messages' => (int) ($this->inbound_messages ?? 0),
            'outbound_messages' => (int) ($this->outbound_messages ?? 0),
            'assigned_to' => $this->whenLoaded('assignedTo', fn () => [
                'id' => $this->assignedTo?->id,
                'name' => $this->assignedTo?->name,
                'email' => $this->assignedTo?->email,
            ]),
            'lead' => $this->whenLoaded('lead', fn () => [
                'uuid' => $this->lead?->uuid,
                'full_name' => $this->lead?->fullName(),
                'source' => $this->lead?->source?->value,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
