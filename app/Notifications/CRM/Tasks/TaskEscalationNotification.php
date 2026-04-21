<?php

declare(strict_types=1);

namespace App\Notifications\CRM\Tasks;

use App\Models\CRM\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// BRD: CRM-TF-004 — Escalation alert sent to manager/role when overdue threshold is exceeded
final class TaskEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $escalateTo,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $overdueHours = (int) $this->task->due_at?->diffInHours(now());
        $assigneeName = $this->task->assignee?->name ?? 'Unknown Counsellor';

        return (new MailMessage)
            ->subject("Task Escalation: {$this->task->title}")
            ->line("A task assigned to {$assigneeName} is overdue and requires attention.")
            ->line("Task: {$this->task->title}")
            ->line("Overdue by: {$overdueHours} hour(s)")
            ->line("Type: {$this->task->type?->label()}")
            ->action('View Team Tasks', url('/crm/manager/team-tasks'))
            ->line('Please review and take appropriate action.');
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
            'assignee_id'   => $this->task->assigned_to,
        ];
    }
}
