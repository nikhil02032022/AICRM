<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\AutomationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AutomationWorkflow $resource
 */
class AutomationWorkflowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->label(),
            'trigger_type' => $this->resource->trigger_type,
            'trigger_config' => $this->resource->trigger_config,
            'version' => (int) $this->resource->version,
            'published_at' => $this->resource->published_at?->toIso8601String(),
            'steps_count' => isset($this->resource->steps_count)
                ? (int) $this->resource->steps_count
                : (int) $this->resource->steps()->count(),
            'steps' => $this->resource->steps->map(static fn ($step): array => [
                'uuid' => $step->uuid,
                'order' => (int) $step->step_order,
                'node_type' => $step->node_type?->value,
                'node_type_label' => $step->node_type?->label(),
                'name' => $step->name,
                'config' => $step->config,
                'delay_minutes' => $step->delay_minutes !== null ? (int) $step->delay_minutes : null,
            ])->values()->all(),
            'created_by' => $this->resource->creator?->name,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
