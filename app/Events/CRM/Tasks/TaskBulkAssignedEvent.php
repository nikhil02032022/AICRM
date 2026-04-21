<?php

declare(strict_types=1);

namespace App\Events\CRM\Tasks;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TF-008 — Fired after a bulk task assignment operation completes
final class TaskBulkAssignedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param list<int> $taskIds
     */
    public function __construct(
        public readonly array $taskIds,
        public readonly User $assignee,
    ) {}
}
