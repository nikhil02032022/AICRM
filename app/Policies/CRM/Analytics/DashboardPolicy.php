<?php

declare(strict_types=1);

namespace App\Policies\CRM\Analytics;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AR-001 to CRM-AR-007 — RBAC gates for analytics dashboard access
final class DashboardPolicy
{
    use HandlesAuthorization;

    /** Any authenticated CRM user can view their own analytics scope. */
    public function viewAny(User $user): bool
    {
        return $user->can('crm.analytics.view');
    }

    /** Institution-wide dashboard (leads, applications, enrolments, revenue). */
    public function viewInstitution(User $user): bool
    {
        return $user->can('crm.analytics.institution');
    }

    /** Executive KPI dashboard — directors and admins only. */
    public function viewExecutive(User $user): bool
    {
        return $user->can('crm.analytics.executive');
    }
}
