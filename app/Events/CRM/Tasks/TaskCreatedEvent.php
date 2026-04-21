<?php

declare(strict_types=1);

namespace App\Events\CRM\Tasks;

use App\Models\CRM\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-TF-001 — Fired after a task is created; triggers activity timeline entry
final class TaskCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
    ) {}
}
