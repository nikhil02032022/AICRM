<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM\Tasks;

use App\Models\CRM\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-TF-001 — API resource: never expose internal id or institution_id
/** @mixin Task */
final class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'title'              => $this->title,
            'description'        => $this->description,
            'type'               => $this->type?->value,
            'type_label'         => $this->type?->label(),
            'priority'           => $this->priority?->value,
            'priority_label'     => $this->priority?->label(),
            'status'             => $this->status?->value,
            'status_label'       => $this->status?->label(),
            'disposition'        => $this->disposition?->value,
            'disposition_label'  => $this->disposition?->label(),
            'source'             => $this->source?->value,
            'due_at'             => $this->due_at?->toIso8601String(),
            'completed_at'       => $this->completed_at?->toIso8601String(),
            'overdue_flagged_at' => $this->overdue_flagged_at?->toIso8601String(),
            'lead' => $this->whenLoaded('lead', fn () => [
                'uuid'      => $this->lead->uuid,
                'full_name' => "{$this->lead->first_name} {$this->lead->last_name}",
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id'   => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
