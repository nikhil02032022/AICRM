<?php

declare(strict_types=1);

namespace App\Events\CRM\Tasks;

use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TF-001, CRM-TF-008 — Fired when a task is assigned to a counsellor
final class TaskAssignedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
    ) {}
}
