<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Import;

use App\Enums\CRM\ImportBatchStatus;
use App\Models\CRM\LeadImportBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

// BRD: CRM-LC-012 — Eloquent repository for import batch tracking
final class EloquentLeadImportBatchRepository implements LeadImportBatchRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data, int $institutionId): LeadImportBatch
    {
        return LeadImportBatch::create(array_merge($data, [
            'institution_id' => $institutionId,
        ]));
    }

    public function findByUuid(string $uuid): ?LeadImportBatch
    {
        return LeadImportBatch::where('uuid', $uuid)->first();
    }

    public function findByUuidOrFail(string $uuid): LeadImportBatch
    {
        return LeadImportBatch::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function update(LeadImportBatch $batch, array $data): LeadImportBatch
    {
        $batch->update($data);

        return $batch->refresh();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        $query = LeadImportBatch::where('institution_id', $institutionId)
            ->with('initiatedBy:id,name')
            ->orderByDesc('created_at');

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Atomically increment counters — safe for concurrent job chunks.
     * BRD: CRM-LC-012 — parallel BulkLeadImportJob chunks update the same batch row.
     */
    public function incrementCounters(LeadImportBatch $batch, int $processed, int $failed): void
    {
        DB::table('lead_import_batches')
            ->where('id', $batch->id)
            ->update([
                'processed_rows' => DB::raw("processed_rows + {$processed}"),
                'failed_rows'    => DB::raw("failed_rows + {$failed}"),
                'updated_at'     => now(),
            ]);
    }
}
