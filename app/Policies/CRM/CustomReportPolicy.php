<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\CustomReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AR-018, CRM-AR-020 — RBAC for custom reports and schedules
final class CustomReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('crm.reports.view');
    }

    public function view(User $user, CustomReport $report): bool
    {
        return $user->can('crm.reports.view')
            && $user->institution_id === $report->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.reports.manage');
    }

    public function update(User $user, CustomReport $report): bool
    {
        return $user->can('crm.reports.manage')
            && $user->institution_id === $report->institution_id;
    }

    public function delete(User $user, CustomReport $report): bool
    {
        return $user->can('crm.reports.manage')
            && $user->institution_id === $report->institution_id;
    }

    public function export(User $user, CustomReport $report): bool
    {
        return $user->can('crm.reports.export')
            && $user->institution_id === $report->institution_id;
    }
}
