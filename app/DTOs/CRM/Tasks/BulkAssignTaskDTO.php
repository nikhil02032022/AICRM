<?php

declare(strict_types=1);

namespace App\DTOs\CRM\Tasks;

// BRD: CRM-TF-008 — Bulk task assignment validated with tenant isolation
final readonly class BulkAssignTaskDTO
{
    /**
     * @param list<string> $taskUuids
     */
    public function __construct(
        public array $taskUuids,
        public int $assigneeId,
        public int $institutionId,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated, int $institutionId): self
    {
        return new self(
            taskUuids:     $validated['task_uuids'],
            assigneeId:    (int) $validated['assigned_to'],
            institutionId: $institutionId,
        );
    }
}
