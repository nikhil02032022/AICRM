<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\CustomReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CustomReport
 *
 * BRD: CRM-AR-018 — API resource for custom report definitions
 */
class CustomReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'name'            => $this->name,
            'description'     => $this->description,
            'entity'          => $this->entity->value,
            'entity_label'    => $this->entity->label(),
            'selected_fields' => $this->selected_fields,
            'filters'         => $this->filters,
            'group_by'        => $this->group_by,
            'sort_field'      => $this->sort_field,
            'sort_direction'  => $this->sort_direction,
            'last_run_at'     => $this->last_run_at?->toIso8601String(),
            'created_by'      => $this->whenLoaded('createdBy', fn () => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
