<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\LeadAttribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LeadAttribution $resource
 */
final class LeadAttributionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'lead_uuid' => $this->resource->lead?->uuid,
            'touch_type' => $this->resource->touch_type,
            'source' => $this->resource->source,
            'utm_source' => $this->resource->utm_source,
            'utm_medium' => $this->resource->utm_medium,
            'utm_campaign' => $this->resource->utm_campaign,
            'utm_term' => $this->resource->utm_term,
            'utm_content' => $this->resource->utm_content,
            'touchpoint_at' => $this->resource->touchpoint_at?->toIso8601String(),
            'is_first_touch' => $this->resource->is_first_touch,
            'is_last_touch' => $this->resource->is_last_touch,
            'first_touch_credit' => (float) $this->resource->first_touch_credit,
            'last_touch_credit' => (float) $this->resource->last_touch_credit,
            'linear_credit' => (float) $this->resource->linear_credit,
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
