<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CustomField
 *
 * BRD: CRM-EC-005 — API resource for custom field definitions
 */
class CustomFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'entity'             => $this->entity->value,
            'field_key'          => $this->field_key,
            'label'              => $this->label,
            'type'               => $this->type->value,
            'type_label'         => $this->type->label(),
            'options'            => $this->options,
            'is_required'        => $this->is_required,
            'is_visible_in_list' => $this->is_visible_in_list,
            'sort_order'         => $this->sort_order,
            'is_active'          => $this->is_active,
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
