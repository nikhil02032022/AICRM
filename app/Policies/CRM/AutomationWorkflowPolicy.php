<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\AutomationWorkflow;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-MA-001 — Institution-scoped access policy for automation workflow management
class AutomationWorkflowPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AutomationWorkflow $workflow): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $workflow->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.campaigns.manage');
    }

    public function edit(User $user, AutomationWorkflow $workflow): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $workflow->institution_id;
    }

    public function delete(User $user, AutomationWorkflow $workflow): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $workflow->institution_id;
    }
}
