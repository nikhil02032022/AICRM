<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-LQ-007
final class ScoreOverrideResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'lead_uuid'         => $this->lead?->uuid,
            'previous_score'    => $this->previous_score,
            'overridden_score'  => $this->overridden_score,
            'reason'            => $this->reason,
            'overridden_by'     => [
                'id'   => $this->overriddenBy?->id,
                'name' => $this->overriddenBy?->name,
            ],
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
