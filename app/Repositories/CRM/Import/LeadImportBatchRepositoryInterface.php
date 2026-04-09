<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Import;

use App\Enums\CRM\ImportBatchStatus;
use App\Models\CRM\LeadImportBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeadImportBatchRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data, int $institutionId): LeadImportBatch;

    public function findByUuid(string $uuid): ?LeadImportBatch;

    public function findByUuidOrFail(string $uuid): LeadImportBatch;

    /** @param array<string, mixed> $data */
    public function update(LeadImportBatch $batch, array $data): LeadImportBatch;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $institutionId, int $perPage = 20): LengthAwarePaginator;

    /** Atomically increment processed_rows and failed_rows counters. */
    public function incrementCounters(LeadImportBatch $batch, int $processed, int $failed): void;
}
