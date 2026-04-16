<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\Application;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AP-008, CRM-AP-009 — RBAC for application pipeline management
class ApplicationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Application $application): bool
    {
        return $user->can('crm.applications.view') && $user->institution_id === $application->institution_id;
    }

    public function transition(User $user, Application $application): bool
    {
        return $user->can('crm.applications.edit') && $user->institution_id === $application->institution_id;
    }

    public function bulkAction(User $user): bool
    {
        return $user->can('crm.applications.edit');
    }
}
