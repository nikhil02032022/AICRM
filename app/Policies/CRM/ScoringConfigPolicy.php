<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\InstitutionScoringConfig;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: A01 Broken Access Control — explicit RBAC gates for scoring operations
final class ScoringConfigPolicy
{
    use HandlesAuthorization;

    /**
     * Institution admins and super admins can configure scoring weights/thresholds.
     * BRD: CRM-LQ-001, CRM-LQ-005
     */
    public function update(User $user, InstitutionScoringConfig $config = null): bool
    {
        return $user->hasAnyRole(['institution-admin', 'super-admin']);
    }

    /**
     * Counsellors, institution admins, and super admins can view the source quality report.
     * BRD: CRM-LQ-008
     */
    public function viewReport(User $user, InstitutionScoringConfig $config = null): bool
    {
        return $user->hasAnyRole(['senior-counsellor', 'junior-counsellor', 'institution-admin', 'super-admin']);
    }

    /**
     * Assigned counsellor or admin can override a lead's score.
     * BRD: CRM-LQ-007
     */
    public function override(User $user, Lead $lead): bool
    {
        if ($user->hasAnyRole(['institution-admin', 'super-admin'])) {
            return true;
        }

        // Counsellors may only override leads assigned to them
        return $user->hasAnyRole(['senior-counsellor', 'junior-counsellor'])
            && (int) $lead->assigned_counsellor_id === $user->id;
    }
}
