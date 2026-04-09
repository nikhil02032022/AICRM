<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-LC-001 — API Resource for WebForm — exposed to React Native / A2A ERP consumers
/**
 * @property \App\Models\CRM\WebForm $resource
 */
class WebFormResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid'                 => $this->resource->uuid,
            'name'                 => $this->resource->name,
            'slug'                 => $this->resource->slug,
            'fields'               => $this->resource->fields,
            'is_active'            => $this->resource->is_active,
            'source'               => $this->resource->source?->value,
            'source_label'         => $this->resource->source?->label(),
            'public_url'           => $this->resource->publicUrl(),
            'embed_url'            => $this->resource->embedUrl(),
            'redirect_url'         => $this->resource->redirect_url,
            'consent_form_version' => $this->resource->consent_form_version,
            'accent_color'         => $this->resource->accent_color,
            'logo_url'             => $this->resource->logo_url,
            'created_at'           => $this->resource->created_at?->toIso8601String(),
            'updated_at'           => $this->resource->updated_at?->toIso8601String(),
            // Deliberately omitted: id, institution_id, embed_token (internal only)
        ];
    }
}
