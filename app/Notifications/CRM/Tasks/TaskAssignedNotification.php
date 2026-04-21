<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Tasks;

use App\Models\CRM\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-TF-001 — Notify counsellor when a new task is assigned to them
final class TaskAssignedNotification extends Notification
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
        return (new MailMessage)
            ->subject("New Task Assigned: {$this->task->title}")
            ->line("A new task has been assigned to you.")
            ->line("Type: {$this->task->type?->label()}")
            ->line("Priority: {$this->task->priority?->label()}")
            ->line("Due: {$this->task->due_at?->format('d M Y H:i')}")
            ->action('View Task', url("/crm/tasks/{$this->task->uuid}/edit"))
            ->line('Please complete this task by the due date.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'task_uuid' => $this->task->uuid,
            'type'      => $this->task->type?->value,
            'priority'  => $this->task->priority?->value,
            'due_at'    => $this->task->due_at?->toIso8601String(),
            'title'     => $this->task->title,
        ];
    }
}
