<?php

declare(strict_types=1);

namespace App\Events\CRM\Tasks;

use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TF-004 — Fired when an overdue task is escalated to a manager role
final class TaskOverdueEscalatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $escalateTo,
    ) {}
}
