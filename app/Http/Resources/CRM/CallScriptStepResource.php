<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TC-002 — API representation of a call script step
final class CallScriptStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'step_key' => $this->step_key,
            'step_order' => $this->step_order,
            'prompt_text' => $this->prompt_text,
            'response_type' => $this->response_type?->value,
            'options' => $this->options,
            'branch_rules' => $this->branch_rules,
            'default_next_step_key' => $this->default_next_step_key,
            'is_terminal' => $this->is_terminal,
        ];
    }
}
