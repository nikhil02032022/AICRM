<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\WorkflowTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkflowTemplate
 *
 * BRD: CRM-SA-007 — API resource for workflow automation templates
 */
class WorkflowTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'description'   => $this->description,
            'category'      => $this->category->value,
            'category_label'=> $this->category->label(),
            'trigger_type'  => $this->trigger_type,
            'template_data' => $this->template_data,
            'is_global'     => $this->is_global,
            'is_active'     => $this->is_active,
            'sort_order'    => $this->sort_order,
            'used_count'    => $this->used_count,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
