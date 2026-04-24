<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\CRM\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.admin.audit-logs.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('crm.admin.audit-logs.view')
            && $user->institution_id === (int) $auditLog->institution_id;
    }

    // Audit logs are append-only — no create/update/delete from UI
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
