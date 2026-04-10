<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\IntegrationChannel;
use App\Enums\CRM\LeadSource;
use App\Repositories\CRM\Import\LeadImportBatchRepositoryInterface;
use App\Services\CRM\Lead\LeadService;
use App\Services\CRM\WebForm\PublicFormActor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// BRD: CRM-LC-012 — Processes a single chunk of CSV rows from a bulk import batch
// Part of a Bus::batch() — individual row failures are recorded, not the whole batch
final class BulkLeadImportJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        /** @var array<int, array<string, mixed>> */
        public readonly array $rows,
        public readonly string $batchUuid,
        public readonly string $channel,
        public readonly int $institutionId,
        public readonly ?int $campusId,
    ) {
        $this->onQueue('crm-imports');
    }

    public function handle(
        LeadService $leadService,
        LeadImportBatchRepositoryInterface $batchRepository,
    ): void {
        // If the batch was cancelled via Horizon, skip processing
        if ($this->batch()?->cancelled()) {
            return;
        }

        $source = IntegrationChannel::from($this->channel)->toLeadSource();
        $actor = new PublicFormActor(institutionId: $this->institutionId);

        $processed = 0;
        $failed = 0;
        $errorRows = [];

        foreach ($this->rows as $index => $row) {
            try {
                $dto = $this->rowToDto($row, $source);
                $leadService->create($dto, $actor);
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                // Record the failing row with a reason for the error report CSV
                $errorRows[] = array_merge($row, ['_error' => $e->getMessage()]);

                // BRD: CRM-CR-002 — No PII in logs
                Log::warning('BulkLeadImportJob: row failed', [
                    'batch_uuid' => $this->batchUuid,
                    'row_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Find and increment counters atomically
        $batch = $batchRepository->findByUuid($this->batchUuid);

        if ($batch !== null) {
            $batchRepository->incrementCounters($batch, $processed, $failed);
        }

        // Append failed rows to the per-batch error report CSV on S3
        if (!empty($errorRows) && $batch !== null) {
            $this->appendErrorReport($errorRows, $batch->institution_id);
        }
    }

    /**
     * Map a CSV row array to a CreateLeadDTO.
     * Supports both explicit first_name/last_name and combined "name" column.
     *
     * @param  array<string, mixed>  $row
     */
    private function rowToDto(array $row, LeadSource $source): CreateLeadDTO
    {
        // Name handling
        if (!empty($row['first_name'])) {
            $firstName = trim((string) $row['first_name']);
            $lastName = trim((string) ($row['last_name'] ?? 'Unknown'));
        } elseif (!empty($row['name'])) {
            $parts = explode(' ', trim((string) $row['name']), 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? 'Unknown';
        } else {
            throw new \InvalidArgumentException('Row missing first_name or name column.');
        }

        $mobile = preg_replace('/\D/', '', (string) ($row['mobile'] ?? '')) ?? '';

        // Strip country code
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        }

        if (strlen($mobile) < 10) {
            throw new \InvalidArgumentException("Invalid mobile number: {$mobile}");
        }

        // Allow row-level source override; fall back to channel-derived source
        $rowSource = $row['source'] ?? $source->value;

        $utmParams = array_filter([
            'utm_source' => $row['utm_source'] ?? null,
            'utm_medium' => $row['utm_medium'] ?? null,
            'utm_campaign' => $row['utm_campaign'] ?? null,
        ]);

        return new CreateLeadDTO(
            firstName: $firstName,
            lastName: $lastName,
            mobile: $mobile,
            email: !empty($row['email']) ? (string) $row['email'] : null,
            source: $rowSource,
            // BRD: CRM-CR-001 — Consent attested by the person uploading the file
            consentGiven: true,
            consentIp: null,
            consentFormVersion: 'channel:bulk_csv:v1',
            campusId: $this->campusId,
            city: !empty($row['city']) ? (string) $row['city'] : null,
            state: !empty($row['state']) ? (string) $row['state'] : null,
            notes: !empty($row['notes']) ? (string) $row['notes'] : null,
            sourceUtmParams: !empty($utmParams) ? $utmParams : null,
            programmeIds: null,
        );
    }

    /**
     * Append failed rows to a per-batch error report CSV on S3.
     * BRD: CRM-LC-012 — Downloadable error report for the importer.
     *
     * @param  array<int, array<string, mixed>>  $errorRows
     */
    private function appendErrorReport(array $errorRows, int $institutionId): void
    {
        $path = "institutions/{$institutionId}/lead-imports/errors/{$this->batchUuid}-errors.csv";
        $disk = Storage::disk('local');
        $headers = array_keys($errorRows[0]);
        $lines = [];

        // Only add header row if file does not exist yet
        if (!$disk->exists($path)) {
            $lines[] = implode(',', array_map([$this, 'escapeCsvCell'], $headers));
        }

        foreach ($errorRows as $row) {
            $lines[] = implode(',', array_map([$this, 'escapeCsvCell'], array_values($row)));
        }

        $disk->append($path, implode("\n", $lines));
    }

    private function escapeCsvCell(mixed $value): string
    {
        $str = (string) $value;

        // Wrap in quotes if value contains comma, newline, or quote
        if (str_contains($str, ',') || str_contains($str, "\n") || str_contains($str, '"')) {
            return '"'.str_replace('"', '""', $str).'"';
        }

        return $str;
    }

    public function failed(\Throwable $e): void
    {
        Log::error('BulkLeadImportJob: chunk permanently failed', [
            'batch_uuid' => $this->batchUuid,
            'institution_id' => $this->institutionId,
            'error' => $e->getMessage(),
        ]);
    }
}
