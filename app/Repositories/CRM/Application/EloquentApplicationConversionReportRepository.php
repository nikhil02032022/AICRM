<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentApplicationConversionReportRepository implements ApplicationConversionReportRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getGroupedConversionStats(array $filters = []): Collection
    {
        $query = ApplicationConversionLog::query()
            ->with(['application', 'lead', 'convertedBy'])
            ->where('status', 'success');

        if (!empty($filters['from_date'])) {
            $query->whereDate('completed_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('completed_at', '<=', $filters['to_date']);
        }
        if (!empty($filters['programme_id'])) {
            $query->whereHas('application', function (Builder $q) use ($filters) {
                $q->where('programme_id', $filters['programme_id']);
            });
        }
        if (!empty($filters['source'])) {
            $query->whereHas('lead', function (Builder $q) use ($filters) {
                $q->where('source', $filters['source']);
            });
        }
        if (!empty($filters['counsellor_id'])) {
            $query->whereHas('application', function (Builder $q) use ($filters) {
                $q->where('assigned_counsellor_id', $filters['counsellor_id']);
            });
        }
        if (!empty($filters['batch'])) {
            $query->whereHas('lead', function (Builder $q) use ($filters) {
                $q->where('preferred_intake', $filters['batch']);
            });
        }

        // Group by programme, source, counsellor
        $results = $query->get()->groupBy(function ($log) {
            $source = $log->lead->source;
            $sourceValue = $source instanceof \BackedEnum ? $source->value : (string) $source;
            return $log->application->programme_id . '|' . $sourceValue . '|' . $log->application->assigned_counsellor_id;
        });

        // Map to stats
        return $results->map(function ($group) {
            $first = $group->first();
            $source = $first->lead->source ?? null;
            return [
                'programme_id' => $first->application->programme_id ?? null,
                'programme_name' => $first->application->programme->name ?? null,
                'source' => $source instanceof \BackedEnum ? $source->value : $source,
                'counsellor_id' => $first->application->assigned_counsellor_id ?? null,
                'counsellor_name' => $first->application->assignedCounsellor->name ?? null,
                'conversions' => $group->count(),
                'from_date' => optional($group->min('completed_at'))->toDateString(),
                'to_date' => optional($group->max('completed_at'))->toDateString(),
            ];
        })->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getConversionRates(array $filters = []): Collection
    {
        $enrolledValue = ApplicationStatus::ENROLLED->value;

        $query = Application::query()
            ->join('leads', 'leads.uuid', '=', 'applications.lead_uuid')
            ->leftJoin('crm_programmes', 'crm_programmes.id', '=', 'applications.programme_id')
            ->leftJoin('users', 'users.id', '=', 'applications.assigned_counsellor_id')
            ->select([
                'applications.programme_id',
                'crm_programmes.name as programme_name',
                DB::raw('leads.preferred_intake as batch'),
                DB::raw('leads.source as source'),
                'applications.assigned_counsellor_id as counsellor_id',
                DB::raw('users.name as counsellor_name'),
                DB::raw('COUNT(*) as total_applications'),
                DB::raw("SUM(CASE WHEN applications.status = '{$enrolledValue}' THEN 1 ELSE 0 END) as enrolled_count"),
            ])
            ->whereNull('applications.deleted_at');

        if (!empty($filters['from_date'])) {
            $query->whereDate('applications.submitted_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('applications.submitted_at', '<=', $filters['to_date']);
        }
        if (!empty($filters['programme_id'])) {
            $query->where('applications.programme_id', $filters['programme_id']);
        }
        if (!empty($filters['source'])) {
            $query->where('leads.source', $filters['source']);
        }
        if (!empty($filters['counsellor_id'])) {
            $query->where('applications.assigned_counsellor_id', $filters['counsellor_id']);
        }
        if (!empty($filters['batch'])) {
            $query->where('leads.preferred_intake', $filters['batch']);
        }

        return $query
            ->groupBy([
                'applications.programme_id',
                'crm_programmes.name',
                'leads.preferred_intake',
                'leads.source',
                'applications.assigned_counsellor_id',
                'users.name',
            ])
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_applications;
                $enrolled = (int) $row->enrolled_count;

                return [
                    'programme_id'       => $row->programme_id,
                    'programme_name'     => $row->programme_name,
                    'batch'              => $row->batch,
                    'source'             => $row->source instanceof \BackedEnum ? $row->source->value : $row->source,
                    'counsellor_id'      => $row->counsellor_id,
                    'counsellor_name'    => $row->counsellor_name,
                    'total_applications' => $total,
                    'enrolled_count'     => $enrolled,
                    'conversion_rate'    => $total > 0 ? round(($enrolled / $total) * 100, 2) : 0.0,
                ];
            })
            ->values();
    }
}
