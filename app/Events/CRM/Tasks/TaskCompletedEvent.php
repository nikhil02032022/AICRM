<?php

declare(strict_types=1);

namespace App\Events\CRM\Tasks;

use App\Models\CRM\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TF-005 — Fired after task completion with disposition
final class TaskCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
    ) {}
}
