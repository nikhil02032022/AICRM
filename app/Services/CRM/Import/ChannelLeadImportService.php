<?php

declare(strict_types=1);

namespace App\Services\CRM\Import;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\IntegrationChannel;
use App\Events\CRM\DigitalLeadImportedEvent;
use App\Jobs\CRM\DetectLeadDuplicatesJob;
use App\Models\CRM\Lead;
use App\Services\CRM\Import\Normalizers\NormalizerContract;
use App\Services\CRM\Lead\LeadService;
use App\Services\CRM\WebForm\PublicFormActor;
use Illuminate\Support\Facades\Log;

/**
 * ChannelLeadImportService — Central orchestrator for all digital channel lead imports.
 *
 * Called by every webhook job (Google, Meta, portal) after normalisation.
 * Delegates actual lead creation to LeadService (reuses LC-011, CR-001, LC-018 logic).
 *
 * BRD: CRM-LC-003, CRM-LC-004, CRM-LC-008
 * BRD: CRM-CR-001 — Consent captured via normalizer-set DTO fields
 */
final class ChannelLeadImportService
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    /**
     * Import a single lead from a digital channel using the provided normalizer.
     *
     * @param  array<string, mixed>  $raw         Raw platform payload
     * @param  NormalizerContract    $normalizer   Channel-specific field mapper
     * @param  int                   $institutionId  Institution owning the credential
     * @param  string                $platformIp   IP of the inbound webhook (not student IP)
     */
    public function importFromChannel(
        array $raw,
        NormalizerContract $normalizer,
        int $institutionId,
        string $platformIp,
    ): Lead {
        // Normalise platform payload → typed DTO
        $dto = $normalizer->normalize($raw, $institutionId, $platformIp);

        // System actor — no real authenticated user in webhook context
        $actor = new PublicFormActor(institutionId: $institutionId);

        // LeadService::create() handles: DB persist, score job, dedup job, LeadCreatedEvent
        $lead = $this->leadService->create($dto, $actor);

        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Digital channel lead imported', [
            'lead_uuid'      => $lead->uuid,
            'institution_id' => $institutionId,
        ]);

        // Fire DigitalLeadImportedEvent — listeners can extend without modifying this service
        DigitalLeadImportedEvent::dispatch($lead);

        return $lead;
    }
}
