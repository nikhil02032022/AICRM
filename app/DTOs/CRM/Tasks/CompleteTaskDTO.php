<?php

declare(strict_types=1);

namespace App\DTOs\CRM\Tasks;

use App\Enums\CRM\Tasks\TaskDisposition;
use Carbon\Carbon;

// BRD: CRM-TF-005 — Disposition is mandatory on task completion
final readonly class CompleteTaskDTO
{
    public function __construct(
        public int $taskId,
        public ?TaskDisposition $disposition,
        public ?string $notes,
        public Carbon $completedAt,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(int $taskId, array $validated): self
    {
        return new self(
            taskId:      $taskId,
            disposition: TaskDisposition::from($validated['disposition']),
            notes:       $validated['notes'] ?? null,
            completedAt: Carbon::now(),
        );
    }
}
