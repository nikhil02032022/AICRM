<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Analytics;

use App\Models\CRM\CustomReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomReportRepositoryInterface
{
    public function findByUuidOrFail(string $uuid): CustomReport;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomReport;

    /** @param array<string, mixed> $data */
    public function update(CustomReport $report, array $data): CustomReport;

    public function delete(CustomReport $report): void;

    // BRD: CRM-AR-018 — Touch last_run_at when a report is executed
    public function touchLastRunAt(CustomReport $report): void;
}
