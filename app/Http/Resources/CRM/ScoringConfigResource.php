<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-LQ-001, CRM-LQ-005
final class ScoringConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'institution_id'  => $this->institution_id,
            'weights'         => $this->weights,
            'hot_threshold'   => $this->hot_threshold,
            'warm_threshold'  => $this->warm_threshold,
            'is_active'       => $this->is_active,
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
