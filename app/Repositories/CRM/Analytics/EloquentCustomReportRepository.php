<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Analytics;

use App\Models\CRM\CustomReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCustomReportRepository implements CustomReportRepositoryInterface
{
    public function findByUuidOrFail(string $uuid): CustomReport
    {
        return CustomReport::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = CustomReport::with('createdBy:id,name')
            ->select(['id', 'uuid', 'name', 'entity', 'description', 'created_by', 'last_run_at', 'created_at']);

        if (!empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $term);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomReport
    {
        return CustomReport::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(CustomReport $report, array $data): CustomReport
    {
        $report->update($data);

        return $report->fresh();
    }

    public function delete(CustomReport $report): void
    {
        $report->delete();
    }

    // BRD: CRM-AR-018 — Record when this report was last executed
    public function touchLastRunAt(CustomReport $report): void
    {
        $report->update(['last_run_at' => now()]);
    }
}
