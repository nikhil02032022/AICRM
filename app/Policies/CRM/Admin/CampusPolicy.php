<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\CRM\Campus;
use App\Models\User;

class CampusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.admin.campuses.manage');
    }

    public function view(User $user, Campus $campus): bool
    {
        return $user->can('crm.admin.campuses.manage')
            && $user->institution_id === $campus->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.admin.campuses.manage');
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->can('crm.admin.campuses.manage')
            && $user->institution_id === $campus->institution_id;
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $user->can('crm.admin.campuses.manage')
            && $user->institution_id === $campus->institution_id;
    }
}
