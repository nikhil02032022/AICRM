<?php

declare(strict_types=1);

namespace App\Jobs\CRM;

use App\Repositories\CRM\Import\IntegrationCredentialRepositoryInterface;
use App\Services\CRM\Import\ChannelLeadImportService;
use App\Services\CRM\Import\Normalizers\MetaLeadNormalizer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMetaLeadJob — Two-phase Meta Lead Ads import.
 *
 * Phase 1: Meta webhook delivers only a leadgen_id (fast ACK required).
 * Phase 2 (this job): Fetch full lead form response from Meta Graph API,
 *                     normalise, and create the lead record.
 *
 * Uses the page_access_token stored in the IntegrationCredential credentials JSON.
 *
 * BRD: CRM-LC-004 — Meta Lead Ads API auto-import
 * OWASP A05 — access token retrieved from encrypted integration_credentials, never hardcoded
 */
final class ProcessMetaLeadJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public int $backoff = 60;

    private const META_GRAPH_VERSION = 'v19.0';

    public function __construct(
        public readonly string $leadgenId,
        public readonly string $integrationUuid,
        public readonly int $institutionId,
        public readonly string $platformIp,
    ) {
        $this->onQueue('crm-imports');
    }

    /** Unique key prevents double-processing on Meta platform retries. */
    public function uniqueId(): string
    {
        return "meta-lead:{$this->institutionId}:{$this->leadgenId}";
    }

    public function handle(
        ChannelLeadImportService $importService,
        MetaLeadNormalizer $normalizer,
        IntegrationCredentialRepositoryInterface $credentialRepo,
    ): void {
        // Fetch credential (without scope — no auth in job context)
        $credential = $credentialRepo->findActiveByUuidWithoutScope($this->integrationUuid);

        if ($credential === null) {
            Log::warning('ProcessMetaLeadJob: credential not found or inactive', [
                'integration_uuid' => $this->integrationUuid,
                'institution_id' => $this->institutionId,
            ]);
            // Discard — no retry, credential was deactivated
            $this->delete();

            return;
        }

        $accessToken = $credential->getCredential('page_access_token');

        if (empty($accessToken)) {
            Log::error('ProcessMetaLeadJob: page_access_token missing in credential', [
                'integration_uuid' => $this->integrationUuid,
            ]);

            // Fail so it shows in Horizon — admin needs to reconfigure
            throw new \RuntimeException('Meta page_access_token not configured for integration '.$this->integrationUuid);
        }

        // Fetch full lead form data from Meta Graph API
        $leadData = $this->fetchFromGraphApi($this->leadgenId, $accessToken);

        if ($leadData === null) {
            throw new \RuntimeException("Meta Graph API returned null for leadgen_id: {$this->leadgenId}");
        }

        $importService->importFromChannel(
            raw: $leadData,
            normalizer: $normalizer,
            institutionId: $this->institutionId,
            platformIp: $this->platformIp,
        );
    }

    /**
     * Fetch lead form data from Meta Graph API.
     *
     * @return array<string, mixed>|null
     */
    private function fetchFromGraphApi(string $leadgenId, string $accessToken): ?array
    {
        $client = new Client(['timeout' => 30.0]);

        try {
            $response = $client->get(
                'https://graph.facebook.com/'.self::META_GRAPH_VERSION."/{$leadgenId}",
                [
                    'query' => [
                        'fields' => 'id,created_time,ad_id,ad_name,adset_name,campaign_name,platform,field_data',
                        'access_token' => $accessToken,
                    ],
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('ProcessMetaLeadJob: Graph API request failed', [
                'leadgen_id' => $leadgenId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMetaLeadJob: permanently failed', [
            'leadgen_id' => $this->leadgenId,
            'institution_id' => $this->institutionId,
            'error' => $e->getMessage(),
        ]);
    }
}
