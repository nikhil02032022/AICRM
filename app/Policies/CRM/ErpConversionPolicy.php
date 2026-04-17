<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\User;

// BRD: CRM-AP-016 — Authorization policies for ERP conversion operations
final class ErpConversionPolicy
{
    /**
     * Can the user view conversion logs?
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'institution-admin', 'admissions-staff'])
            || $user->hasPermissionTo('crm.applications.view');
    }

    /**
     * Can the user view a single conversion log?
     */
    public function view(User $user, ApplicationConversionLog $log): bool
    {
        return $user->institution_id === $log->institution_id
            && ($user->hasRole(['admin', 'institution-admin', 'admissions-staff'])
                || $user->hasPermissionTo('crm.applications.view'));
    }

    /**
     * Can the user trigger ERP conversion for this application?
     */
    public function convert(User $user, Application $application): bool
    {
        return $user->institution_id === $application->institution_id
            && ($user->hasRole(['admin', 'institution-admin', 'admissions-staff'])
                || $user->hasPermissionTo('crm.applications.convert'));
    }

    /**
     * Can the user retry a failed conversion?
     */
    public function retry(User $user, ApplicationConversionLog $log): bool
    {
        return $user->institution_id === $log->institution_id
            && ($user->hasRole(['admin', 'institution-admin', 'admissions-staff'])
                || $user->hasPermissionTo('crm.applications.convert'));
    }
}
