<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\User;

// BRD: CRM-AR-007 — Resolve the data scope for authenticated user: counsellor sees own data, manager sees team, director sees institution
final class DashboardScopeService
{
    /**
     * Resolve the analytics scope for the given user.
     *
     * @return array{
     *   institution_id: int,
     *   campus_id: int|null,
     *   counsellor_ids: list<int>|null,
     *   role: string
     * }
     */
    public function resolveScope(User $user): array
    {
        $institutionId = (int) $user->institution_id;
        $campusId      = $user->campus_id ? (int) $user->campus_id : null;

        if ($user->hasRole(['admissions_director', 'institution-admin', 'super-admin'])) {
            return [
                'institution_id'  => $institutionId,
                'campus_id'       => null,  // no campus restriction for director
                'counsellor_ids'  => null,  // null = all counsellors
                'role'            => 'director',
            ];
        }

        if ($user->hasRole('admissions_manager')) {
            // Manager sees their campus; counsellor_ids resolved by caller if needed
            return [
                'institution_id' => $institutionId,
                'campus_id'      => $campusId,
                'counsellor_ids' => null,   // null = all counsellors in campus
                'role'           => 'manager',
            ];
        }

        // Default: counsellor scope — own data only
        return [
            'institution_id' => $institutionId,
            'campus_id'      => $campusId,
            'counsellor_ids' => [$user->id],
            'role'           => 'counsellor',
        ];
    }

    /** Convenience: check if the scope covers the full institution (no counsellor restriction). */
    public function isInstitutionWide(array $scope): bool
    {
        return $scope['counsellor_ids'] === null;
    }
}
