<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM\Tasks;

use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TF-002 — API resource for task auto-rules
/** @mixin TaskAutoRule */
final class TaskAutoRuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid'                       => $this->uuid,
            'trigger_type'               => $this->trigger_type,
            'inactivity_threshold_hours' => $this->inactivity_threshold_hours,
            'task_type'                  => $this->task_type,
            'priority'                   => $this->priority,
            'assignee_strategy'          => $this->assignee_strategy,
            'is_active'                  => $this->is_active,
            'campus_id'                  => $this->campus_id,
            'created_at'                 => $this->created_at?->toIso8601String(),
        ];
    }
}
