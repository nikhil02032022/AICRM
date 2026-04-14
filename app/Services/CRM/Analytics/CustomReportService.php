<?php

declare(strict_types=1);

namespace App\Services\CRM\Analytics;

use App\Models\CRM\CustomReport;
use App\Models\CRM\ReportExport;
use App\Repositories\CRM\Analytics\CustomReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AR-018 — Custom report builder: run, export, and manage report definitions
final class CustomReportService
{
    public function __construct(
        private readonly CustomReportRepositoryInterface $repository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $institutionId, int $userId): CustomReport
    {
        $data['institution_id'] = $institutionId;
        $data['created_by']     = $userId;

        return $this->repository->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(CustomReport $report, array $data): CustomReport
    {
        return $this->repository->update($report, $data);
    }

    public function delete(CustomReport $report): void
    {
        $this->repository->delete($report);
    }

    /**
     * BRD: CRM-AR-018 — Execute the report query and return paginated rows.
     *
     * @return array{headers: list<string>, rows: list<array<string, mixed>>, total: int}
     */
    public function run(CustomReport $report, int $perPage = 100): array
    {
        $this->repository->touchLastRunAt($report);

        $query = $this->buildQuery($report);

        $total = (clone $query)->count();

        // Apply sort
        $sortField = $report->sort_field ?? 'created_at';
        $sortDir   = $report->sort_direction ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        $rows = $query->limit($perPage)->get()->toArray();

        Log::info('Custom report executed', [
            'report_uuid' => $report->uuid,
            'entity'      => $report->entity->value,
            'row_count'   => count($rows),
        ]);

        return [
            'headers' => $report->selected_fields,
            'rows'    => $rows,
            'total'   => $total,
        ];
    }

    /**
     * BRD: CRM-AR-018 — Record export action for DPDP audit trail.
     *
     * Actual file generation is dispatched as a background job (not in this service).
     */
    public function recordExport(CustomReport $report, int $userId, string $format, int $rowCount, ?string $storagePath): ReportExport
    {
        return ReportExport::create([
            'institution_id'   => $report->institution_id,
            'custom_report_id' => $report->id,
            'exported_by'      => $userId,
            'format'           => $format,
            'storage_path'     => $storagePath,
            'row_count'        => $rowCount,
            'expires_at'       => now()->addDays(7),
        ]);
    }

    // BRD: CRM-AR-018 — Build the raw DB query based on report entity + selected_fields + filters
    private function buildQuery(CustomReport $report): \Illuminate\Database\Query\Builder
    {
        $table = $report->entity->value; // e.g. 'leads', 'applications'

        $query = DB::table($table)
            ->where("{$table}.institution_id", $report->institution_id)
            ->whereNull("{$table}.deleted_at")
            ->select($report->selected_fields);

        // Apply filters: [{field, operator, value}]
        foreach (($report->filters ?? []) as $filter) {
            if (!isset($filter['field'], $filter['operator'], $filter['value'])) {
                continue;
            }
            $query->where($filter['field'], $filter['operator'], $filter['value']);
        }

        // Group by
        if (!empty($report->group_by)) {
            $query->groupBy($report->group_by)->addSelect(DB::raw("COUNT(*) as count"));
        }

        return $query;
    }
}
