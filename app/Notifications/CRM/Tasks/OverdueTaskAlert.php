<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Tasks;

use App\Models\CRM\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-TF-004 — Alert sent to task assignee when their task becomes overdue
final class OverdueTaskAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $overdueHours = (int) $this->task->due_at?->diffInHours(now());

        return (new MailMessage)
            ->subject("Overdue Task: {$this->task->title}")
            ->line("Your task is overdue by {$overdueHours} hour(s).")
            ->line("Task: {$this->task->title}")
            ->line("Type: {$this->task->type?->label()}")
            ->line("Was due: {$this->task->due_at?->format('d M Y H:i')}")
            ->action('Complete Task', url("/crm/tasks/{$this->task->uuid}/complete"))
            ->line('Please complete or update this task immediately.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'task_uuid'     => $this->task->uuid,
            'title'         => $this->task->title,
            'type'          => $this->task->type?->value,
            'due_at'        => $this->task->due_at?->toIso8601String(),
            'overdue_hours' => (int) $this->task->due_at?->diffInHours(now()),
        ];
    }
}
