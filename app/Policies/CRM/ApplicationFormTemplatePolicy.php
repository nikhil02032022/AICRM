<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\ApplicationFormTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AP-001 — RBAC for application form template management
class ApplicationFormTemplatePolicy
{
    use HandlesAuthorization;

    public function view(User $user, ApplicationFormTemplate $template): bool
    {
        return $user->can('crm.applications.view') && $user->institution_id === $template->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.applications.create');
    }

    public function edit(User $user, ApplicationFormTemplate $template): bool
    {
        return $user->can('crm.applications.edit') && $user->institution_id === $template->institution_id;
    }

    public function delete(User $user, ApplicationFormTemplate $template): bool
    {
        return $user->can('crm.applications.delete') && $user->institution_id === $template->institution_id;
    }
}
