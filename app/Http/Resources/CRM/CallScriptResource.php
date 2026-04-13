<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-002 — API representation of a call script and its branching steps
final class CallScriptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'status' => $this->status?->value,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'steps' => CallScriptStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
