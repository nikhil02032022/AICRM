<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Services\CRM\Import\ChannelLeadImportService;
use App\Services\CRM\Import\Normalizers\GoogleLeadNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-003 — Process a single Google Lead Form Extensions webhook payload
// Job is idempotent — unique key on lead_id prevents double-processing on platform retry
final class ProcessGoogleLeadJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        /** @var array<string, mixed> */
        public readonly array $payload,
        public readonly int $institutionId,
        public readonly string $platformIp,
    ) {
        $this->onQueue('crm-imports');
    }

    /** Unique key prevents double-processing of the same Google lead on platform retry. */
    public function uniqueId(): string
    {
        $leadId = $this->payload['lead_id'] ?? 'unknown';

        return "google-lead:{$this->institutionId}:{$leadId}";
    }

    public function handle(
        ChannelLeadImportService $importService,
        GoogleLeadNormalizer $normalizer,
    ): void {
        // BRD: CRM-CR-002 — No PII in logs
        Log::info('ProcessGoogleLeadJob: processing', [
            'institution_id' => $this->institutionId,
            'lead_id' => $this->payload['lead_id'] ?? 'unknown',
        ]);

        $importService->importFromChannel(
            raw: $this->payload,
            normalizer: $normalizer,
            institutionId: $this->institutionId,
            platformIp: $this->platformIp,
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessGoogleLeadJob: failed', [
            'institution_id' => $this->institutionId,
            'lead_id' => $this->payload['lead_id'] ?? 'unknown',
            'error' => $e->getMessage(),
        ]);
    }
}
