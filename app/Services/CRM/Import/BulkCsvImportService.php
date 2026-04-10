<?php

declare(strict_types=1);

namespace App\Services\CRM\Import;

use App\Enums\CRM\ImportBatchStatus;
use App\Enums\CRM\IntegrationChannel;
use App\Events\CRM\BulkImportCompletedEvent;
use App\Jobs\CRM\BulkLeadImportJob;
use App\Models\CRM\LeadImportBatch;
use App\Repositories\CRM\Import\LeadImportBatchRepositoryInterface;
use Illuminate\Bus\Batch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;

// BRD: CRM-LC-012 — Bulk CSV/Excel import: parse → chunk → Bus::batch() → error report
final class BulkCsvImportService
{
    private const CHUNK_SIZE = 100;

    public function __construct(
        private readonly LeadImportBatchRepositoryInterface $batchRepository,
    ) {}

    /**
     * Accept an uploaded file, store it on S3, parse it into chunks,
     * and dispatch a Bus::batch() of BulkLeadImportJob instances.
     *
     * BRD: CRM-LC-012 — Returns the created LeadImportBatch for progress tracking.
     * BRD: CRM-CR-001 — Consent attestation is validated in BulkLeadImportRequest before calling this.
     */
    public function dispatch(
        UploadedFile $file,
        IntegrationChannel $channel,
        int $institutionId,
        int $initiatedByUserId,
        ?int $campusId = null,
    ): LeadImportBatch {
        // Store original file on local disk (private — not publicly accessible)
        $storedPath = Storage::disk('local')->putFile(
            "institutions/{$institutionId}/lead-imports",
            $file,
        );

        // Count rows for progress tracking
        // Pass the original extension explicitly — getRealPath() returns a .tmp file which
        // SimpleExcelReader cannot recognise by extension.
        $rows = $this->parseRows($file->getRealPath(), $file->getClientOriginalExtension());
        $totalRows = count($rows);

        // Create the batch record BEFORE dispatching — Horizon can see it immediately
        $batch = $this->batchRepository->create([
            'channel' => $channel->value,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => ImportBatchStatus::PENDING->value,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'failed_rows' => 0,
            'initiated_by_user_id' => $initiatedByUserId,
        ], $institutionId);

        if ($totalRows === 0) {
            $this->batchRepository->update($batch, [
                'status' => ImportBatchStatus::COMPLETED->value,
            ]);

            return $batch;
        }

        // Chunk rows and dispatch a Bus::batch()
        $chunks = array_chunk($rows, self::CHUNK_SIZE);
        $jobs = array_map(
            fn (array $chunk) => new BulkLeadImportJob(
                rows: $chunk,
                batchUuid: $batch->uuid,
                channel: $channel->value,
                institutionId: $institutionId,
                campusId: $campusId,
            ),
            $chunks,
        );

        $jobBatch = Bus::batch($jobs)
            ->name("lead-import:{$batch->uuid}")
            ->allowFailures()  // partial success is acceptable
            ->then(function (Batch $jobBatch) use ($batch): void {
                $this->onBatchComplete($batch, $jobBatch);
            })
            ->catch(function (Batch $jobBatch, \Throwable $e) use ($batch): void {
                $this->onBatchFailed($batch, $e);
            })
            ->onQueue('crm-imports')
            ->dispatch();

        // Record the Laravel job_batch ID for Horizon progress tracking
        $this->batchRepository->update($batch, [
            'status' => ImportBatchStatus::PROCESSING->value,
            'job_batch_id' => $jobBatch->id,
        ]);

        Log::info('BulkCsvImportService: batch dispatched', [
            'batch_uuid' => $batch->uuid,
            'total_rows' => $totalRows,
            'chunks' => count($chunks),
            'institution' => $institutionId,
        ]);

        return $batch->refresh();
    }

    /**
     * Called when all jobs in the batch finish (success or allowFailures partial).
     */
    private function onBatchComplete(LeadImportBatch $batch, Batch $jobBatch): void
    {
        // Reload the batch to get latest counters written by the jobs
        $batch = $batch->fresh();

        if ($batch === null) {
            return;
        }

        $hasFailed = $batch->failed_rows > 0;
        $errorReportPath = null;

        // If there are failed rows, the jobs will have written the error report to S3
        if ($hasFailed) {
            $errorReportPath = "institutions/{$batch->institution_id}/lead-imports/errors/{$batch->uuid}-errors.csv";
        }

        $this->batchRepository->update($batch, [
            'status' => ImportBatchStatus::COMPLETED->value,
            'error_report_path' => $errorReportPath,
        ]);

        BulkImportCompletedEvent::dispatch($batch->fresh(), $hasFailed);
    }

    /**
     * Called when the batch itself fails (not individual row failures — those use allowFailures).
     */
    private function onBatchFailed(LeadImportBatch $batch, \Throwable $e): void
    {
        $batch = $batch->fresh();

        if ($batch === null) {
            return;
        }

        Log::error('BulkCsvImportService: batch failed', [
            'batch_uuid' => $batch->uuid,
            'error' => $e->getMessage(),
        ]);

        $this->batchRepository->update($batch, [
            'status' => ImportBatchStatus::FAILED->value,
        ]);

        BulkImportCompletedEvent::dispatch($batch, true);
    }

    /**
     * Parse CSV/XLSX file into an array of row arrays.
     * Uses spatie/simple-excel — no PhpSpreadsheet memory overhead for CSV.
     * The $type parameter must be passed explicitly because the real path is a
     * PHP temp file with a .tmp extension that SimpleExcelReader cannot detect.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseRows(string $filePath, string $type): array
    {
        return SimpleExcelReader::create($filePath, strtolower($type))
            ->trimHeaderRow()
            ->getRows()
            ->filter(fn (array $row) => $this->rowHasRequiredFields($row))
            ->values()
            ->all();
    }

    /**
     * Skip completely empty rows.
     *
     * @param  array<string, mixed>  $row
     */
    private function rowHasRequiredFields(array $row): bool
    {
        return !empty($row['first_name'] ?? $row['name'] ?? null)
            || !empty($row['mobile'] ?? null);
    }
}
