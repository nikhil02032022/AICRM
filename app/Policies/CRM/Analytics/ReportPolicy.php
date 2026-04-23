<?php

declare(strict_types=1);

namespace App\Policies\CRM\Analytics;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AR-009 to CRM-AR-018 — RBAC gates for standard and custom report access
final class ReportPolicy
{
    use HandlesAuthorization;

    /** Run and view standard reports (AR-009 to AR-017). */
    public function viewAny(User $user): bool
    {
        return $user->can('crm.reports.view');
    }

    /** Create, edit, delete, and schedule reports (AR-018, AR-020). */
    public function manage(User $user): bool
    {
        return $user->can('crm.reports.manage');
    }

    /** Export reports to Excel or PDF (AR-019). */
    public function export(User $user): bool
    {
        return $user->can('crm.reports.export');
    }
}
