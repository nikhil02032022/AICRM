<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-003 — API representation of configurable call dispositions
final class CallDispositionConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'label' => $this->label,
            'is_active' => $this->is_active,
            'requires_follow_up' => $this->requires_follow_up,
            'sort_order' => $this->sort_order,
            'is_system' => $this->is_system,
        ];
    }
}
