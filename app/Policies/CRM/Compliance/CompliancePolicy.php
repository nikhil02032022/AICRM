<?php

declare(strict_types=1);

namespace App\Policies\CRM\Compliance;

use App\Models\User;

class CompliancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.compliance.access');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.compliance.incidents.create');
    }

    public function update(User $user): bool
    {
        return $user->can('crm.compliance.incidents.update');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
