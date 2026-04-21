<?php

declare(strict_types=1);

namespace App\Policies\CRM\Tasks;

use App\Models\CRM\Task;
use App\Models\User;

// BRD: CRM-TF-001 to CRM-TF-008 — RBAC for task management; counsellors see own tasks, managers see team tasks
final class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.tasks.index');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->institution_id !== $task->institution_id) {
            return false;
        }

        return $task->assigned_to === $user->id
            || $task->created_by === $user->id
            || $user->can('crm.tasks.team.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->institution_id !== $task->institution_id) {
            return false;
        }

        return ($task->assigned_to === $user->id && $user->can('crm.tasks.create'))
            || $user->can('crm.tasks.edit');
    }

    public function complete(User $user, Task $task): bool
    {
        if ($user->institution_id !== $task->institution_id) {
            return false;
        }

        return $task->assigned_to === $user->id
            || $user->can('crm.tasks.edit');
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->institution_id !== $task->institution_id) {
            return false;
        }

        return $user->can('crm.tasks.delete');
    }
}
