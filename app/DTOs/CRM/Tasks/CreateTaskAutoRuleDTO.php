<?php

declare(strict_types=1);

namespace App\DTOs\CRM\Tasks;

// BRD: CRM-TF-002 — Input for creating an institution-level auto-task rule
final readonly class CreateTaskAutoRuleDTO
{
    public function __construct(
        public int $institutionId,
        public ?int $campusId,
        public string $triggerType,
        public int $inactivityThresholdHours,
        public string $taskType,
        public string $priority,
        public string $assigneeStrategy,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated, int $institutionId): self
    {
        return new self(
            institutionId:            $institutionId,
            campusId:                 isset($validated['campus_id']) ? (int) $validated['campus_id'] : null,
            triggerType:              $validated['trigger_type'],
            inactivityThresholdHours: (int) $validated['inactivity_threshold_hours'],
            taskType:                 $validated['task_type'],
            priority:                 $validated['priority'],
            assigneeStrategy:         $validated['assignee_strategy'],
        );
    }
}
