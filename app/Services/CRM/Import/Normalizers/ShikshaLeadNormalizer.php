<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * ShikshaLeadNormalizer — Maps Shiksha lead API / webhook payload to CreateLeadDTO.
 *
 * Shiksha API lead format:
 * {
 *   "lead_id": "...",
 *   "name": "Arjun Sharma",
 *   "mobile": "9876543210",
 *   "email": "arjun@example.com",
 *   "city": "Mumbai",
 *   "course_interest": "MBA",
 *   "campaign": "Shiksha MBA 2026"
 * }
 *
 * BRD: CRM-LC-008 — Education portal import normaliser (Shiksha)
 */
final class ShikshaLeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        [$firstName, $lastName] = $this->parseName($raw['name'] ?? '');

        return new CreateLeadDTO(
            firstName:          $firstName,
            lastName:           $lastName,
            mobile:             $this->normaliseMobile($raw['mobile'] ?? ''),
            email:              $raw['email'] ?? null,
            source:             LeadSource::EDUCATION_PORTAL->value,
            consentGiven:       true,
            consentIp:          $consentIp,
            consentFormVersion: 'channel:shiksha:v1',
            campusId:           null,
            city:               $raw['city'] ?? null,
            state:              $raw['state'] ?? null,
            notes:              isset($raw['course_interest']) ? 'Course interest: ' . $raw['course_interest'] : null,
            sourceUtmParams:    array_filter(['utm_source' => 'shiksha', 'utm_campaign' => $raw['campaign'] ?? null]),
            programmeIds:       null,
        );
    }

    /** @return array{string, string} */
    private function parseName(string $full): array
    {
        $parts = explode(' ', trim($full), 2);

        return [! empty($parts[0]) ? $parts[0] : 'Unknown', $parts[1] ?? 'Unknown'];
    }

    private function normaliseMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return (strlen($digits) === 12 && str_starts_with($digits, '91')) ? substr($digits, 2) : $digits;
    }
}
