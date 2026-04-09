<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * CollegeDekhoLeadNormalizer — Maps CollegeDekho lead API / webhook payload to CreateLeadDTO.
 *
 * CollegeDekho API lead format:
 * {
 *   "lead_id": "...",
 *   "first_name": "Arjun",
 *   "last_name": "Sharma",
 *   "phone": "9876543210",
 *   "email": "arjun@example.com",
 *   "city": "Mumbai",
 *   "course": "MBA",
 *   "college_name": "ABC B-School"
 * }
 *
 * BRD: CRM-LC-008 — Education portal import normaliser (CollegeDekho)
 */
final class CollegeDekhoLeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        return new CreateLeadDTO(
            firstName:          ! empty($raw['first_name']) ? $raw['first_name'] : 'Unknown',
            lastName:           ! empty($raw['last_name']) ? $raw['last_name'] : 'Unknown',
            mobile:             $this->normaliseMobile($raw['phone'] ?? $raw['mobile'] ?? ''),
            email:              $raw['email'] ?? null,
            source:             LeadSource::EDUCATION_PORTAL->value,
            consentGiven:       true,
            consentIp:          $consentIp,
            consentFormVersion: 'channel:college_dekho:v1',
            campusId:           null,
            city:               $raw['city'] ?? null,
            state:              $raw['state'] ?? null,
            notes:              isset($raw['course']) ? 'Course interest: ' . $raw['course'] : null,
            sourceUtmParams:    ['utm_source' => 'college_dekho'],
            programmeIds:       null,
        );
    }

    private function normaliseMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return (strlen($digits) === 12 && str_starts_with($digits, '91')) ? substr($digits, 2) : $digits;
    }
}
