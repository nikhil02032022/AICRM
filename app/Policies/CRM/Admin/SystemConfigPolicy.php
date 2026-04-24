<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\CRM\Admin\SystemConfig;
use App\Models\User;

class SystemConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.admin.system-config.manage');
    }

    public function update(User $user, SystemConfig $config): bool
    {
        return $user->can('crm.admin.system-config.manage')
            && $user->institution_id === (int) $config->institution_id;
    }
}
