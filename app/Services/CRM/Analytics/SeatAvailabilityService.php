<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-AR-005 — Seat availability vs confirmed enrolments real-time view
final class SeatAvailabilityService
{
    /**
     * Returns per-programme seat availability for the institution (and optional campus).
     *
     * Enrolment count = lead_programme_interests rows with status = 'enrolled'
     * linked to a non-deleted lead in the scoped institution/campus.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @return Collection<int, object{
     *     id: int, name: string, code: string, level: string, department: string,
     *     intake_capacity: int, confirmed_enrolments: int, available_seats: int,
     *     utilisation_pct: float, status: string
     * }>
     */
    public function getProgrammeSeatData(array $scope): Collection
    {
        $institutionId = $scope['institution_id'];
        $campusId      = $scope['campus_id'];

        $rows = DB::table('crm_programmes as p')
            ->where('p.institution_id', $institutionId)
            ->where('p.is_active', true)
            ->leftJoinSub(
                DB::table('lead_programme_interests as lpi')
                    ->join('leads as l', function ($join) use ($institutionId, $campusId): void {
                        $join->on('l.id', '=', 'lpi.lead_id')
                            ->whereNull('l.deleted_at')
                            ->where('l.institution_id', $institutionId);
                        if ($campusId !== null) {
                            $join->where('l.campus_id', $campusId);
                        }
                    })
                    ->where('lpi.status', 'enrolled')
                    ->select('lpi.crm_programme_id', DB::raw('COUNT(lpi.id) as enrolment_count'))
                    ->groupBy('lpi.crm_programme_id'),
                'enr',
                'enr.crm_programme_id',
                '=',
                'p.id',
            )
            ->select(
                'p.id',
                'p.name',
                'p.code',
                'p.level',
                'p.department',
                'p.intake_capacity',
                DB::raw('COALESCE(enr.enrolment_count, 0) as confirmed_enrolments'),
            )
            ->orderByDesc(DB::raw('COALESCE(enr.enrolment_count, 0)'))
            ->get();

        return $rows->map(function (object $row): object {
            $capacity    = (int) $row->intake_capacity;
            $enrolled    = (int) $row->confirmed_enrolments;
            $available   = max(0, $capacity - $enrolled);
            $utilisation = $capacity > 0 ? round(($enrolled / $capacity) * 100, 1) : 0.0;

            $status = match (true) {
                $capacity === 0           => 'uncapped',
                $utilisation >= 100.0     => 'full',
                $utilisation >= 80.0      => 'critical',
                default                   => 'healthy',
            };

            return (object) [
                'id'                   => (int) $row->id,
                'name'                 => $row->name,
                'code'                 => $row->code,
                'level'                => $row->level,
                'department'           => $row->department,
                'intake_capacity'      => $capacity,
                'confirmed_enrolments' => $enrolled,
                'available_seats'      => $available,
                'utilisation_pct'      => $utilisation,
                'status'               => $status,
            ];
        });
    }

    /**
     * Institution-level summary KPIs across all active programmes.
     *
     * @param array{institution_id: int, campus_id: int|null, counsellor_ids: list<int>|null, role: string} $scope
     * @return array{total_capacity: int, total_enrolled: int, total_available: int, programmes_full: int, programmes_critical: int, overall_utilisation: float}
     */
    public function getSummaryKpis(array $scope): array
    {
        $programmes = $this->getProgrammeSeatData($scope);

        $totalCapacity  = $programmes->sum('intake_capacity');
        $totalEnrolled  = $programmes->sum('confirmed_enrolments');
        $totalAvailable = $programmes->sum('available_seats');
        $full           = $programmes->where('status', 'full')->count();
        $critical       = $programmes->where('status', 'critical')->count();
        $utilisation    = $totalCapacity > 0
            ? round(($totalEnrolled / $totalCapacity) * 100, 1)
            : 0.0;

        return [
            'total_capacity'      => (int) $totalCapacity,
            'total_enrolled'      => (int) $totalEnrolled,
            'total_available'     => (int) $totalAvailable,
            'programmes_full'     => $full,
            'programmes_critical' => $critical,
            'overall_utilisation' => $utilisation,
        ];
    }
}
