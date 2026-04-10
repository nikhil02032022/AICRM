<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Enums\CRM\IntegrationChannel;
use App\Repositories\CRM\Import\IntegrationCredentialRepositoryInterface;
use App\Services\CRM\Import\ChannelLeadImportService;
use App\Services\CRM\Import\PortalNormalizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-008 — Process a single education portal webhook lead payload
// Unique key on portal lead ID prevents double-processing on retries
final class ProcessPortalLeadJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        /** @var array<string, mixed> */
        public readonly array $payload,
        public readonly string $channel,
        public readonly string $integrationUuid,
        public readonly int $institutionId,
        public readonly string $platformIp,
    ) {
        $this->onQueue('crm-imports');
    }

    /** Unique key prevents duplicate processing on portal platform retries. */
    public function uniqueId(): string
    {
        $leadId = $this->payload['lead_id'] ?? $this->payload['id'] ?? md5(serialize($this->payload));

        return "portal-lead:{$this->channel}:{$this->institutionId}:{$leadId}";
    }

    public function handle(
        ChannelLeadImportService $importService,
        PortalNormalizerService $normalizerService,
        IntegrationCredentialRepositoryInterface $credentialRepo,
    ): void {
        $credential = $credentialRepo->findActiveByUuidWithoutScope($this->integrationUuid);

        if ($credential === null) {
            Log::warning('ProcessPortalLeadJob: credential not found or inactive', [
                'integration_uuid' => $this->integrationUuid,
                'channel' => $this->channel,
            ]);
            $this->delete();

            return;
        }

        $channel = IntegrationChannel::from($this->channel);
        $normalizer = $normalizerService->resolve($channel);

        $importService->importFromChannel(
            raw: $this->payload,
            normalizer: $normalizer,
            institutionId: $this->institutionId,
            platformIp: $this->platformIp,
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessPortalLeadJob: permanently failed', [
            'channel' => $this->channel,
            'institution_id' => $this->institutionId,
            'error' => $e->getMessage(),
        ]);
    }
}
