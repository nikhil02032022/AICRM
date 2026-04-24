<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\CRM\Institution;
use App\Models\User;

class InstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.admin.institutions.view');
    }

    public function view(User $user, Institution $institution): bool
    {
        return $user->can('crm.admin.institutions.view')
            && ($user->institution_id === $institution->id || $user->can('crm.admin.institutions.view.all'));
    }

    public function update(User $user, Institution $institution): bool
    {
        return $user->can('crm.admin.institutions.edit')
            && ($user->institution_id === $institution->id || $user->can('crm.admin.institutions.view.all'));
    }
}
