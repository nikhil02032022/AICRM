<?php

declare(strict_types=1);

namespace App\DTOs\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskType;
use Carbon\Carbon;

// BRD: CRM-TF-001 — Typed, immutable input object for task creation
final readonly class CreateTaskDTO
{
    public function __construct(
        public int $leadId,
        public TaskType $type,
        public TaskPriority $priority,
        public string $title,
        public ?string $description,
        public Carbon $dueAt,
        public ?int $assignedTo,
        public TaskSource $source = TaskSource::Manual,
        public ?int $autoRuleId = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            leadId:      (int) $validated['lead_id'],
            type:        TaskType::from($validated['type']),
            priority:    TaskPriority::from($validated['priority']),
            title:       $validated['title'],
            description: $validated['description'] ?? null,
            dueAt:       Carbon::parse($validated['due_at']),
            assignedTo:  isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
        );
    }
}
