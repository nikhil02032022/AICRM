<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;

/**
 * NormalizerContract — Every channel-specific lead normalizer implements this interface.
 *
 * Each normalizer maps the platform's raw payload field names to a CreateLeadDTO,
 * sets the correct LeadSource enum value, sets consent fields, and returns a
 * ready-to-use DTO that LeadService::create() can accept directly.
 *
 * BRD: CRM-LC-003, CRM-LC-004, CRM-LC-008
 */
interface NormalizerContract
{
    /**
     * Normalise a raw platform payload into a CreateLeadDTO.
     *
     * @param  array<string, mixed>  $raw        Raw webhook / CSV row payload from the platform
     * @param  int                   $institutionId  Resolved from the IntegrationCredential
     * @param  string                $consentIp  IP of the inbound webhook request
     */
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO;
}
