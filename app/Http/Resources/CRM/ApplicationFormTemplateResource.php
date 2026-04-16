<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\ApplicationFormTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ApplicationFormTemplate $resource
 */
class ApplicationFormTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'sections' => $this->resource->sections,
            'progression_rules' => $this->resource->progression_rules,
            'settings' => $this->resource->settings,
            'minimum_completeness_percentage' => $this->resource->minimum_completeness_percentage,
            'is_active' => $this->resource->is_active,
            'version' => $this->resource->version,
            'published_at' => $this->resource->published_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            // Deliberately omitted: id, institution_id, campus_id, created_by
        ];
    }
}
